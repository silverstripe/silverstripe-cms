<?php

/**
 * @todo Make this use the Task interface once it gets merged back into trunk
 */ 
class RebuildStaticCacheTask extends Controller {
	function init() {
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
		
		
		if(!empty($_GET['urls'])) $urls = $_GET['urls'];
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
			$url = Director::makeRelative($url);
			if(substr($url,-1) == '/') $url = substr($url,0,-1);
			$urls[$i] = $url;
		}
		$urls = array_unique($urls);
		
		if($removeAll && file_exists("../cache")) {
			echo "Removing old cache... \n";
			flush();
			Filesystem::removeFolder("../cache", true);
			echo "done.\n\n";
		}

		echo  "Republishing " . sizeof($urls) . " urls...\n\n";
		$page->publishPages($urls);
		echo "\n\n== Done! ==";
	}
}
