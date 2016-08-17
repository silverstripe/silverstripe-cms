<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\Versioning\Versioned;

/**
 * Works a bit different than the other filters:
 * Shows all pages *including* those deleted from stage and live.
 * It does not filter out pages still existing in the different stages.
 *
 * @package cms
 * @subpackage content
 */
class CMSSiteTreeFilter_DeletedPages extends CMSSiteTreeFilter
{

	/**
	 * @var string
	 */
	protected $childrenMethod = "AllHistoricalChildren";

	/**
	 * @var string
	 */
	protected $numChildrenMethod = 'numHistoricalChildren';

	static public function title()
	{
		return _t('CMSSiteTreeFilter_DeletedPages.Title', "All pages, including archived");
	}

	public function getFilteredPages()
	{
		$pages = Versioned::get_including_deleted('SilverStripe\\CMS\\Model\\SiteTree');
		$pages = $this->applyDefaultFilters($pages);
		return $pages;
	}
}
