<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\DataObject;

// @TODO What a pointless class!!!!!!
class CMSPagesController extends CMSMain
{
    private static $url_segment = 'pages';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $url_priority = 40;

    private static $menu_title = 'Pages';

    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    public function LinkPreview()
    {
        return false;
    }

    public function isCurrentRecord(DataObject $record)
    {
        return false;
    }
}
