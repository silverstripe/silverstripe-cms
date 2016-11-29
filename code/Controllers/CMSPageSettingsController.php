<?php

namespace SilverStripe\CMS\Controllers;

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

    public function Breadcrumbs($unlinked = false)
    {
        $crumbs = parent::Breadcrumbs($unlinked);
        $crumbs[0]->Title = _t('CMSPagesController.MENUTITLE');
        return $crumbs;
    }
}
