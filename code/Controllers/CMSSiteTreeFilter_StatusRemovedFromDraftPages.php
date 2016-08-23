<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * Filters pages which have a status "Removed from Draft".
 */
class CMSSiteTreeFilter_StatusRemovedFromDraftPages extends CMSSiteTreeFilter
{

	static public function title()
	{
		return _t('CMSSiteTreeFilter_StatusRemovedFromDraftPages.Title', 'Live but removed from draft');
	}

	/**
	 * Filters out all pages who's status is set to "Removed from draft".
	 *
	 * @return SS_List
	 */
	public function getFilteredPages()
	{
		$pages = Versioned::get_including_deleted('SilverStripe\\CMS\\Model\\SiteTree');
		$pages = $this->applyDefaultFilters($pages);
		$pages = $pages->filterByCallback(function (SiteTree $page) {
			// If page is removed from stage but not live
			return $page->getIsDeletedFromStage() && $page->getExistsOnLive();
		});
		return $pages;
	}
}
