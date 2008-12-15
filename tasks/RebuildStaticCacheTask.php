<?php

/**
 * @todo Make this use the Task interface once it gets merged back into trunk
 */ 
class RebuildStaticCacheTask extends Controller {
	function init() {
		Versioned::reading_stage('live');

		if(!Director::is_cli() && !Director::isDev() && !Permission::check("ADMIN")) Security::permissionFailure();
		parent::init();
	}

	function index() {
		StaticPublisher::set_echo_progress(true);

		$page = singleton('Page');
		if(!$page->hasMethod('allPagesToCache')) {
			user_error(
				'RebuildStaticCacheTask::index(): Please define a method "allPagesToCache()" on your Page class to return all pages affected by a cache refresh.', 
				E_USER_ERROR
			);
		}
		
		
		if($_GET['urls']) $urls = $_GET['urls'];
		else $urls = $page->allPagesToCache();
		
		$this->rebuildCache($urls, true);
	}
	
	/**
	 * Rebuilds the static cache for the pages passed through via $urls
	 * @param array $urls The URLs of pages to re-fetch and cache.
	 */
	function rebuildCache($urls, $removeAll = true) {
		if(!is_array($urls)) return; // $urls must be an array
		
		if(!Director::is_cli()) echo "<pre>\n";
		echo "Rebuilding cache.\nNOTE: Please ensure that this page ends with 'Done!' - if not, then something may have gone wrong.\n\n";
		
		$page = singleton('Page');
		
		foreach($urls as $i => $url) {
			if($url && !is_string($url)) {
				user_error("Bad URL: " . var_export($url, true), E_USER_WARNING);
				continue;
			}

			$url = Director::makeRelative($url);
			// Exclude absolute links
			if(preg_match('/^https?:/', $url)) {
				unset($urls[$i]);
			} else {
				if(substr($url,-1) == '/') $url = substr($url,0,-1);
				$urls[$i] = $url;
			}
		}
		$urls = array_unique($urls);
		sort($urls);

		$start = isset($_GET['start']) ? $_GET['start'] : 0;
		$count = isset($_GET['count']) ? $_GET['count'] : sizeof($urls);
		if(($start + $count) > sizeof($urls)) $count = sizeof($urls) - $start;

		$urls = array_slice($urls, $start, $count);

		if(!isset($_GET['urls']) && $start == 0) {
			echo "Removing old cache... ";
			Filesystem::removeFolder("../cache", true);
			echo "done.\n\n";
		}
		echo  "Republishing " . sizeof($urls) . " urls...\n\n";
		$page->publishPages($urls);
		echo "\n\n== Done! ==";
	}
	
	function show() {
		$urls = singleton('Page')->allPagesToCache();
		echo "<pre>\n";
		print_r($urls);
		echo "\n</pre>";
	}
}
