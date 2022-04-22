<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use stdClass;

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

    public function isCurrentPage(DataObject $record)
    {
        return false;
    }

    public function Breadcrumbs($unlinked = false)
    {
        $this->beforeExtending('updateBreadcrumbs', function (ArrayList $items) {
            //special case for building the breadcrumbs when calling the listchildren Pages ListView action
            if ($parentID = $this->getRequest()->getVar('ParentID')) {
                $page = SiteTree::get()->byID($parentID);

                //build a reversed list of the parent tree
                $pages = [];
                while ($page) {
                    array_unshift($pages, $page); //add to start of array so that array is in reverse order
                    $page = $page->Parent;
                }

                //turns the title and link of the breadcrumbs into template-friendly variables
                $params = array_filter([
                    'view' => $this->getRequest()->getVar('view'),
                    'q' => $this->getRequest()->getVar('q')
                ]);
                foreach ($pages as $page) {
                    $params['ParentID'] = $page->ID;
                    $item = new stdClass();
                    $item->Title = $page->Title;
                    $item->Link = Controller::join_links($this->Link(), '?' . http_build_query($params ?? []));
                    $items->push(new ArrayData($item));
                }
            }
        });
        return parent::Breadcrumbs($unlinked);
    }
}
