<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Forms\Form;
use SilverStripe\Model\ArrayData;

class CMSPageSettingsController extends CMSMain
{

    private static $url_segment = 'pages/settings';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $url_priority = 42;

    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    public function getEditForm($id = null, $fields = null): Form
    {
        $record = $this->getRecord($id ?: $this->currentRecordID());

        // @TODO ideally settings isn't its own special thing...
        // can we refactor this so it's just another tab in the main form? And just have it lazyload or something?
        // At the very least this tab must NOT appear if there are no fields for it.
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
