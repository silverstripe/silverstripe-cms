<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use stdClass;

/**
 * @deprecated 5.4.0 Will be combined back into SilverStripe\CMS\Controllers\CMSMain in a future major release
 */
class CMSPagesController extends CMSMain
{

    private static $url_segment = 'pages';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $url_priority = 40;

    private static $menu_title = 'Pages';

    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    public function __construct()
    {
        Deprecation::noticeWithNoReplacment(
            '5.4.0',
            'Will be combined back into ' . CMSMain::class . ' in a future major release',
            Deprecation::SCOPE_CLASS
        );
        parent::__construct();
    }

    public function LinkPreview()
    {
        return false;
    }

    public function isCurrentPage(DataObject $record)
    {
        return false;
    }
}
