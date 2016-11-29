<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Model\ErrorPage;

/**
 * Decorates {@see File} with ErrorPage support
 */
class ErrorPageFileExtension extends DataExtension
{

    /**
     * Used by {@see File::handle_shortcode}
     *
     * @param int $statusCode HTTP Error code
     * @return DataObject Substitute object suitable for handling the given error code
     */
    public function getErrorRecordFor($statusCode)
    {
        return ErrorPage::get()->filter("ErrorCode", $statusCode)->first();
    }
}
