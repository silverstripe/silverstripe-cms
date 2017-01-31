<?php

namespace SilverStripe\CMS\Logging;

use SilverStripe\CMS\Model\ErrorPage;
use SilverStripe\Control\Director;
use SilverStripe\Logging\DebugViewFriendlyErrorFormatter;

/**
 * Provides {@see ErrorPage}-gnostic error handling
 */
class ErrorPageErrorFormatter extends DebugViewFriendlyErrorFormatter
{

    public function output($statusCode)
    {
        // Ajax content is plain-text only
        if (Director::is_ajax()) {
            return $this->getTitle();
        }

        // Determine if cached ErrorPage content is available
        $content = ErrorPage::get_content_for_errorcode($statusCode);
        if ($content) {
            return $content;
        }

        // Fallback to default output
        return parent::output($statusCode);
    }
}
