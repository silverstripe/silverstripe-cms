<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\ORM\DataObject;

/**
 * This interface lets us set up objects that will tell us what the current page is.
 */
interface CurrentRecordIdentifier
{

    /**
     * Get the current page ID.
     * @return int
     */
    public function currentRecordID();

    /**
     * Check if the given DataObject is the current page.
     * @param DataObject $page The page to check.
     * @return boolean
     */
    public function isCurrentRecord(DataObject $page);
}
