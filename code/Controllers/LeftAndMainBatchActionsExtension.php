<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Core\Extension;

/**
 * @extends Extension<LeftAndMain>
 */
class LeftAndMainBatchActionsExtension extends Extension
{
    public function updateBatchActionsForm(&$form)
    {
        $cmsMain = singleton(CMSMain::class);
        $form->Fields()->insertAfter('Action', $cmsMain->BatchActionParameters());
        return $form;
    }
}
