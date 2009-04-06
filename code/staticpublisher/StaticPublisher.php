<?php

abstract class StaticPublisher extends DataObjectDecorator {
	/**
	 * Defines whether to output information about publishing or not. By 
	 * default, this is off, and should be turned on when you want debugging 
	 * (for example, in a cron task)
	 */
	static $echo_progress = false;
	
	abstract function publishPages($pages);
	
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

	function onAfterPublish($original) {
		$urls = array();
		
		if($this->owner->hasMethod('pagesAffectedByChanges')) {
			$urls = $this->owner->pagesAffectedByChanges($original);
		} else {
			$pages = Versioned::get_by_stage('SiteTree', 'Live', '', '', '', 10);
			if($pages) {
				foreach($pages as $page) {
					$urls[] = $page->Link();
				}
			}
		}
		
		foreach($urls as $i => $url) {
			$url = Director::makeRelative($url);
			if(substr($url,-1) == '/') $url = substr($url,0,-1);
			$urls[$i] = $url;
		}

		$urls = array_unique($urls);

		$this->publishPages($urls);
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