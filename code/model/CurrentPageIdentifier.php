<?php
/**
 * This interface lets us set up objects that will tell us what the current page is.
 * @package cms
 * @subpackage model
 */
interface CurrentPageIdentifier {
	/**
	 * Get the current page ID.
	 * @return int
	 */
	public function currentPageID();
	
	/**
	 * Check if the given DataObject is the current page.
	 * @param DataObject $page The page to check.
	 * @return boolean
	 */
	public function isCurrentPage(DataObject $page);
}

