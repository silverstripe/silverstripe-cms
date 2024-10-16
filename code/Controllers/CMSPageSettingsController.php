<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Forms\Form;

class CMSPageSettingsController extends CMSMain
{
    private static $url_segment = 'pages/settings';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $url_priority = 42;

    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    private static $ignore_menuitem = true;

    public function getEditForm($id = null, $fields = null): Form
    {
        $record = $this->getRecord($id ?: $this->currentRecordID());
        if ($record && $record->hasMethod('getSettingsFields')) {
            $fields = $record->getSettingsFields();
        } else {
            $fields = null;
        }
        return parent::getEditForm($id, $fields);
    }

    public function getTabIdentifier()
    {
        return 'settings';
    }
}
