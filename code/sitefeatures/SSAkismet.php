<?php
/**
 * @package cms
 * @subpackage comments
 */
class SSAkismet extends Akismet {
	private static $apiKey;
	private static $saveSpam = true;
	
	static function setAPIKey($key) {
		self::$apiKey = $key;
	}
	
	static function isEnabled() {
		return (self::$apiKey != null) ? true : false;
	}
	
	static function setSaveSpam($save = true) {
		SSAkismet::$saveSpam = $save;
	}
	
	static function getSaveSpam() {
		return SSAkismet::$saveSpam;
	}
	
	public function __construct() {
		parent::__construct(Director::absoluteBaseURL(), self::$apiKey);
	}
}
?>