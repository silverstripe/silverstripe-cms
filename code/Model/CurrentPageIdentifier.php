<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\ORM\DataObject;

/**
 * This interface lets us set up objects that will tell us what the current page is.
 * @deprecated 5.4.0 Will be renamed to SilverStripe\CMS\Model\CurrentRecordIdentifier
 */
interface CurrentPageIdentifier
{

    /**
     * Get the current page ID.
     * @return int
     * @deprecated 5.4.0 Will be renamed to currentRecordID()
     */
    public function currentPageID();

    /**
     * Check if the given DataObject is the current page.
     * @param DataObject $page The page to check.
     * @return boolean
     * @deprecated 5.4.0 Will be renamed to isCurrentRecord()
     */
    public function isCurrentPage(DataObject $page);
}
