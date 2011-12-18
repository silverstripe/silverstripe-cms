<?php
/**
 * @package cms
 * @subpackage tasks
 * 
 * @todo Make this use the Task interface once it gets merged back into trunk
 */ 
class RebuildStaticCacheTask extends Controller {

	static $allowed_actions = array(
		'index', 
	);

	function init() {
		parent::init();
		
		Versioned::reading_stage('live');

		$canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
		if(!$canAccess) return Security::permissionFailure($this);
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
	 *
	 * @param array $urls The URLs of pages to re-fetch and cache.
	 * @param bool $removeAll Remove all stale cache files (default TRUE).
	 */
	function rebuildCache($urls, $removeAll = true) {

		if(!is_array($urls)) {
			// $urls must be an array	
			user_error("Rebuild cache must be passed an array of urls. Make sure your allPagesToCache function returns an array", E_USER_ERROR);
			return;
		}; 
		
		if(!Director::is_cli()) echo "<pre>\n";
		echo "Rebuilding cache.\nNOTE: Please ensure that this page ends with 'Done!' - if not, then something may have gone wrong.\n\n";

		$page = singleton('Page');
		$cacheBaseDir = $page->getDestDir();
		
		if(!file_exists($cacheBaseDir)) {
			Filesystem::makeFolder($cacheBaseDir);
		}
		
		if (file_exists($cacheBaseDir.'/lock') && !isset($_REQUEST['force'])) die("There already appears to be a publishing queue running. You can skip warning this by adding ?/&force to the URL.");
		
		touch($cacheBaseDir.'/lock');
		
		// Note: Similiar to StaticPublisher->republish()
		foreach($urls as $i => $url) {
			if($url && !is_string($url)) {
				user_error("Bad URL: " . var_export($url, true), E_USER_WARNING);
				continue;
			}

			// Remove leading slashes from all URLs (apart from the homepage)
			if(substr($url,-1) == '/' && $url != '/') $url = substr($url,0,-1);
			
			$urls[$i] = $url;
		}
		$urls = array_unique($urls);
		sort($urls);

		$mappedUrls = $page->urlsToPaths($urls);
		
		$start = isset($_GET['start']) ? $_GET['start'] : 0;
		$count = isset($_GET['count']) ? $_GET['count'] : sizeof($urls);
		if(($start + $count) > sizeof($urls)) $count = sizeof($urls) - $start;

		$urls = array_slice($urls, $start, $count);

		if($removeAll && !isset($_GET['urls']) && $start == 0 && file_exists("../cache")) {
			echo "Removing stale cache files... \n";
			flush();
			if (FilesystemPublisher::$domain_based_caching) {
				// Glob each dir, then glob each one of those
				foreach(glob(BASE_PATH . '/cache/*', GLOB_ONLYDIR) as $cacheDir) {
					foreach(glob($cacheDir.'/*') as $cacheFile) {
						$searchCacheFile = trim(str_replace($cacheBaseDir, '', $cacheFile), '\/');
						if (!in_array($searchCacheFile, $mappedUrls)) {
							echo " * Deleting $cacheFile\n";
							@unlink($cacheFile);
						}
					}
				}
			} else {
				
			}
			
			echo "done.\n\n";
		}
		echo  "Rebuilding cache from " . sizeof($mappedUrls) . " urls...\n\n";
		$page->extend('publishPages', $mappedUrls);
		
		if (file_exists($cacheBaseDir.'/lock')) unlink($cacheBaseDir.'/lock');
		
		echo "\n\n== Done! ==";
	}
	
	function show() {
		$urls = singleton('Page')->allPagesToCache();
		echo "<pre>\n";
		print_r($urls);
		echo "\n</pre>";
	}
}
