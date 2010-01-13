<?php
/**
 * @package cms
 * @subpackage publishers
 */
abstract class StaticPublisher extends DataObjectDecorator {
	/**
	 * Defines whether to output information about publishing or not. By 
	 * default, this is off, and should be turned on when you want debugging 
	 * (for example, in a cron task)
	 */
	static $echo_progress = false;
	
	// Realtime static publishing... the second a page
	// is saved, it is written to the cache
	static $disable_realtime = false;
	
	abstract function publishPages($pages);
	abstract function unpublishPages($pages);
	
	static function echo_progress() {
		return (boolean)self::$echo_progress;
	}
	
	/**
	 * Either turns on (boolean true) or off (boolean false) the progress indicators.
	 * @see StaticPublisher::$echo_progress
	 */
	static function set_echo_progress($progress) {
		self::$echo_progress = (boolean)$progress;
	}

	/**
	 * Called after a page is published.
	 */
	function onAfterPublish($original) {
		$this->republish($original);
	}
	
	/**
	 * Called after link assets have been renamed, and the live site has been updated, without
	 * an actual publish event.
	 * 
	 * Only called if the published content exists and has been modified.
	 */
	function onRenameLinkedAsset($original) {
		$this->republish($original);
	}
	
	function republish($original) {
		if (self::$disable_realtime) return;

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
		
		foreach($urls as $i => $url) {
			if(!is_string($url)) {
				user_error("Bad URL: " . var_export($url, true), E_USER_WARNING);
				continue;
			}
			if(substr($url,-1) == '/') $url = substr($url,0,-1);
			$urls[$i] = $url;
		}

		$urls = array_unique($urls);

		$this->publishPages($urls);
	}
	
	/**
	 * On after unpublish, get changes and hook into underlying
	 * functionality
	 */
	function onAfterUnpublish($page) {
		if (self::$disable_realtime) return;
		
		// Get the affected URLs
		if($this->owner->hasMethod('pagesAffectedByUnpublishing')) {
			$urls = $this->owner->pagesAffectedByUnpublishing();
			$urls = array_unique($urls);
		} else {
			$urls = array($this->owner->AbsoluteLink());
		}
		
		$legalPages = $this->owner->allPagesToCache();
		
		$urlsToRepublish = array_intersect($urls, $legalPages);
		$urlsToUnpublish = array_diff($urls, $legalPages);

		Debug::dump($urlsToRepublish);
		Debug::dump($urlsToUnpublish);

		$this->unpublishPages($urlsToUnpublish);
		$this->publishPages($urlsToRepublish);
	}
		
	/**
	 * Get all external references to CSS, JS, 
	 */
	function externalReferencesFor($content) {
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
				if($assetsOnly && substr($url,0,7) != 'assets/') continue;

				$urls[] = $url;
			}
		}
		
		return $urls;		
	}
}

?>
