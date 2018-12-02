<?php

namespace SilverStripe\CMS\Tests\Controllers\CMSPageHistoryControllerTest;

use SilverStripe\CMS\Controllers\CMSPageHistoryController;
use SilverStripe\Dev\TestOnly;

/**
 * Used to circumvent potential URL conflicts with the silverstripe/versioned-admin history viewer controller
 * when running unit tests on the legacy CMSPageHistoryController.
 */
class HistoryController extends CMSPageHistoryController implements TestOnly
{
    private static $url_segment = 'pages/legacyhistory';
}
