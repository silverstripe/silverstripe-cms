<?php
/**
 * Base class for HTML cleaning classes.
 * 
 * @package sapphire
 * @subpackage misc
 */
abstract class HTMLCleaner extends Object {
	public static $default_config = array();
	public $config; //configuration variables for HTMLCleaners that support configuration (like Tidy)

	/**
	 * @param  $config the configuration for the cleaner, if necessary
	 */
	public function __construct($config = null) {
		if ($config) $this->config = $config;
		else $this->config = self::$default_config;
	}

	/**
	 * Passed $content, return HTML that has been tidied.
	 * @return string $content HTML, tidied
	 */
	public abstract function cleanHTML($content);

	/**
	 * Experimental inst class to create a default html cleaner class
	 * @return PurifierHTMLCleaner|TidyHTMLCleaner
	 */
	public static function inst() {
		if (class_exists('HTMLPurifier')) return new PurifierHTMLCleaner();
		elseif (class_exists('tidy')) return new TidyHTMLCleaner();
	}
}


/**
 * Cleans HTML using the HTMLPurifier package
 * http://htmlpurifier.org/
 */
class PurifierHTMLCleaner extends HTMLCleaner {
	
	public function cleanHTML($content) {
		$html = new HTMLPurifier();
		$doc = new SS_HTMLValue($html->purify($content));
		return $doc->getContent();
	}
}

/**
 * Cleans HTML using the Tidy package
 * http://php.net/manual/en/book.tidy.php
 */
class TidyHTMLCleaner extends HTMLCleaner {

	static $default_config = array(
		'clean' => true,
		'output-xhtml' => true,
		'show-body-only' => true,
		'wrap' => 0,
		'input-encoding' => 'utf8',
		'output-encoding' => 'utf8'
	);

	public function cleanHTML($content) {
		$tidy = new tidy();
		$output = $tidy->repairString($content, $this->config);
		return $output;
	}
}

?>
