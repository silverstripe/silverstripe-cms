<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\View\ArrayData;

class CMSPageSettingsController extends CMSMain
{

    private static $url_segment = 'pages/settings';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $url_priority = 42;

    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    public function getEditForm($id = null, $fields = null)
    {
        $record = $this->getRecord($id ?: $this->currentPageID());

        return parent::getEditForm($id, ($record) ? $record->getSettingsFields() : null);
    }

    public function getTabIdentifier()
    {
        return 'settings';
    }
}
