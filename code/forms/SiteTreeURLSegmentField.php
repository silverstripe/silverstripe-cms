<?php
/**
 * @package cms
 * @subpackage forms
 */

/**
 * Used to edit the SiteTree->URLSegment property, and suggest input based on the serverside rules
 * defined through {@link SiteTree->generateURLSegment()} and {@link URLSegmentFilter}.
 * 
 * Note: The actual conversion for saving the value takes place in the model layer.
 */
class SiteTreeURLSegmentField extends TextField {
	
	protected $template = 'SiteTreeURLSegmentField';
	
	static $secondaryinfo = false;
	
	static $urlprefix;
	
	static $allowed_actions = array(
		'suggest'
	);
	
	function suggest($request) {
		if(!$request->getVar('value')) return $this->httpError(405);
		$page = $this->getPage();

		// Same logic as SiteTree->onBeforeWrite
		$page->URLSegment = $page->generateURLSegment($request->getVar('value'));
		$count = 2;
		while(!$page->validURLSegment()) {
			$page->URLSegment = preg_replace('/-[0-9]+$/', null, $page->URLSegment) . '-' . $count;
			$count++;
		}
		
		Controller::curr()->getResponse()->addHeader('Content-Type', 'application/json');
		return Convert::raw2json(array('value' => $page->URLSegment));
	}
		
	/**
	 * @return SiteTree
	 */
	function getPage() {
		$idField = $this->getForm()->dataFieldByName('ID');
		return ($idField && $idField->Value()) ? DataObject::get_by_id('SiteTree', $idField->Value()) : singleton('SiteTree');
	}
	
	/**
	 * @param string the secondary text to show
	 */
	function setSecondaryInfo($string){
		if($string) self::$secondaryinfo = $string; 
	}
	
	/**
	 * @return string the secondary text to show in the template
	 */
	function getSecondaryInfo(){
		return self::$secondaryinfo;
	
	}
	
	/**
	 * @param the url that prefixes the page url segment field
	 */
	function setURLPrefix($url){
		self::$urlprefix = $url;
	}
	
	/**
	 * @return the url prefixes the page url segment field to show in template
	 */
	function getURLPrefix(){
		return self::$urlprefix;
	}
	

	function Type() {
		return 'text sitetreeurlsegment';
	}
	
	
}