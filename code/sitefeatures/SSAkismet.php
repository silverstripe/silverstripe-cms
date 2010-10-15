<?php
/**
 * The SSAkismet class provides spam detection for comments using http://akismet.com/. 
 * In order to use it, you must get an API key, which you can get free for non-commercial use by signing 
 * up for a http://www.wordpress.com account. Commercial keys can be bought at http://akismet.com/commercial/.
 * 
 * To enable spam detection, set your API key in _config.php.  
 * The following lines should be added to **mysite/_config.php** 
 * (or to the _config.php in another folder if you're not using mysite). 
 * 
 * <code>
 * SSAkismet::setAPIKey('<your-key>');
 * </code>
 * 
 * You can then view spam for a page by appending <i>?showspam=1</i> to the url, or use the {@link CommentAdmin} in the CMS.
 * 
 * @see http://demo.silverstripe.com/blog Demo of SSAkismet in action
 * 
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