<?php
/**
 * Base class for HTML cleaning classes.
 * 
 * @package sapphire
 * @subpackage misc
 */
abstract class HTMLCleaner  extends Object {
	/**
	 * Passed $content, return HTML that has been tidied.
	 * @return string $content HTML, tidied
	 */
	public abstract function cleanHTML($content);
}

?>
