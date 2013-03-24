<?php
/**
 * @package cms
 * @subpackage publishers
 */
abstract class StaticPublisher extends DataExtension {
	/**
	 * @config
	 * @var boolean Defines whether to output information about publishing or not. By 
	 * default, this is off, and should be turned on when you want debugging 
	 * (for example, in a cron task)
	 */
	private static $echo_progress = false;
	
	/**
	 * @config
	 * @var boolean Realtime static publishing... the second a page
   * is saved, it is written to the cache 
	 */
	private static $disable_realtime = false;
	
	/*
	 * @config
	 * @var boolean This is the current static publishing theme, which can be set at any point
	 * If it's not set, then the last non-null theme, set via Config::inst()->update('SSViewer', 'theme', ) is used
	 * The obvious place to set this is in _config.php
	 */
	private static $static_publisher_theme=false;
	
	abstract public function publishPages($pages);
	abstract public function unpublishPages($pages);

	/**
	 * @deprecated 3.2 Use the "StaticPublisher.static_publisher_theme" config setting instead
	 * @param [type] $theme [description]
	 */
	static public function set_static_publisher_theme($theme){
		Deprecation::notice('3.2', 'Use the "StaticPublisher.static_publisher_theme" config setting instead');
		Config::inst()->update('StaticPublisher', 'static_publisher_theme', $theme);
	}

	/**
	 * @config
	 * @var boolean Includes a timestamp at the bottom of the generated HTML of each file,
	 * which can be useful for debugging issues with stale caches etc.
	 */
	private static $include_caching_metadata = false;
	
	/**
	 * @deprecated 3.2 Use the "StaticPublisher.static_publisher_theme" config setting instead
	 */
	static public function static_publisher_theme(){
		Deprecation::notice('3.2', 'Use the "StaticPublisher.static_publisher_theme" config setting instead');
		return Config::inst()->get('StaticPublisher', 'static_publisher_theme');
	}

	/**
	 * @deprecated 3.2 Use the "StaticPublisher.echo_progress" config setting instead
	 */
	static public function echo_progress() {
		Deprecation::notice('3.2', 'Use the "StaticPublisher.echo_progress" config setting instead');
		return Config::inst()->get('StaticPublisher', 'echo_progress');
	}
	
	/**
	 * Either turns on (boolean true) or off (boolean false) the progress indicators.
	 * @deprecated 3.2 Use the "StaticPublisher.echo_progress" config setting instead
	 * @see StaticPublisher::$echo_progress
	 */
	static public function set_echo_progress($progress) {
		Deprecation::notice('3.2', 'Use the "StaticPublisher.echo_progress" config setting instead');
		Config::inst()->update('StaticPublisher', 'echo_progress', $progress);
	}

	/**
	 * Called after a page is published.
	 */
	public function onAfterPublish($original) {
		$this->republish($original);
	}
	
	/**
	 * Called after link assets have been renamed, and the live site has been updated, without
	 * an actual publish event.
	 * 
	 * Only called if the published content exists and has been modified.
	 */
	public function onRenameLinkedAsset($original) {
		$this->republish($original);
	}
	
	public function republish($original) {
		if (Config::inst()->get('StaticPublisher', 'disable_realtime')) return;

		$urls = array();
		
		if($this->owner->hasMethod('pagesAffectedByChanges')) {
			$urls = $this->owner->pagesAffectedByChanges($original);
		} else {
			$pages = Versioned::get_by_stage('SiteTree', 'Live', '', '', '', 10);
			if($pages) {
				foreach($pages as $page) {
					$urls[] = $page->AbsoluteLink();
				}
			}
		}
		
		// Note: Similiar to RebuildStaticCacheTask->rebuildCache()
		foreach($urls as $i => $url) {
			if(!is_string($url)) {
				user_error("Bad URL: " . var_export($url, true), E_USER_WARNING);
				continue;
			}

			// Remove leading slashes from all URLs (apart from the homepage)
			if(substr($url,-1) == '/' && $url != '/') $url = substr($url,0,-1);
			
			$urls[$i] = $url;
		}

		$urls = array_unique($urls);

		$this->publishPages($urls);
	}
	
	/**
	 * On after unpublish, get changes and hook into underlying
	 * functionality
	 */
	public function onAfterUnpublish($page) {
		if (Config::inst()->get('StaticPublisher', 'disable_realtime')) return;
		
		// Get the affected URLs
		if($this->owner->hasMethod('pagesAffectedByUnpublishing')) {
			$urls = $this->owner->pagesAffectedByUnpublishing();
			$urls = array_unique($urls);
		} else {
			$urls = array($this->owner->AbsoluteLink());
		}
		
		$legalPages = singleton('Page')->allPagesToCache();
		
		$urlsToRepublish = array_intersect($urls, $legalPages);
		$urlsToUnpublish = array_diff($urls, $legalPages);

		$this->unpublishPages($urlsToUnpublish);
		$this->publishPages($urlsToRepublish);
	}
		
	/**
	 * Get all external references to CSS, JS, 
	 */
	public function externalReferencesFor($content) {
		$CLI_content = escapeshellarg($content);
		$tidy = `echo $CLI_content | tidy -numeric -asxhtml`;
		$tidy = preg_replace('/xmlns="[^"]+"/','', $tidy);
		$xContent = new SimpleXMLElement($tidy);
		//Debug::message($xContent->asXML());
		
		$xlinks = array(
			"//link[@rel='stylesheet']/@href" => false,
			"//script/@src" => false,
			"//img/@src" => false,
			"//a/@href" => true,
		);
		
		$urls = array();
		foreach($xlinks as $xlink => $assetsOnly) {
			$matches = $xContent->xpath($xlink);
			if($matches) foreach($matches as $item) {
				$url = $item . '';
				if($assetsOnly && substr($url,0,7) != ASSETS_DIR . '/') continue;

				$urls[] = $url;
			}
		}
		
		return $urls;		
	}
	
	/**
	 * Provides context for this URL, written as an HTML comment to the static file cache,
	 * which can be useful for debugging cache problems. For example, you could track the
	 * event or related page which triggered this republication. The returned data
	 * is unstructured and not intended to be consumed programmatically.
	 * Consider injecting standard HTML <meta> tags instead where applicable.
	 * 
	 * Note: Only used when {@link $include_caching_metadata} is enabled.
	 * 
	 * @param String
	 * @return Array A numeric array of all metadata.
	 */
	function getMetadata($url) {
		return array(
			'Cache generated on ' . date('Y-m-d H:i:s T (O)')
		);
	}
}

