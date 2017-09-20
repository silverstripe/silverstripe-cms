<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;

/**
 * Utility class representing links to different views of a record
 * for CMS authors, usually for {@link SiteTree} objects with "stage" and "live" links.
 * Useful both in the CMS and alongside the page template (for logged in authors).
 * The class can be used for any {@link DataObject} subclass implementing the {@link CMSPreviewable} interface.
 *
 * New item types can be defined by extending the {@link SilverStripeNavigatorItem} class,
 * for example the "cmsworkflow" module defines a new "future state" item with a date selector
 * to view embargoed data at a future point in time. So the item doesn't always have to be a simple link.
 */
class SilverStripeNavigator extends ViewableData
{

    /**
     * @var DataObject|\SilverStripe\ORM\CMSPreviewable
     */
    protected $record;

    /**
     * @param DataObject|\SilverStripe\ORM\CMSPreviewable $record
     */
    public function __construct(CMSPreviewable $record)
    {
        parent::__construct();
        $this->record = $record;
    }

    /**
     * @return SS_List of SilverStripeNavigatorItem
     */
    public function getItems()
    {
        $items = array();

        $classes = ClassInfo::subclassesFor(SilverStripeNavigatorItem::class);
        array_shift($classes);

        // Sort menu items according to priority
        foreach ($classes as $class) {
            /** @var SilverStripeNavigatorItem $item */
            $item = new $class($this->record);
            if (!$item->canView()) {
                continue;
            }

            // This funny litle formula ensures that the first item added with the same priority will be left-most.
            $priority = $item->getPriority() * 100 - 1;

            // Ensure that we can have duplicates with the same (default) priority
            while (isset($items[$priority])) {
                $priority++;
            }

            $items[$priority] = $item;
        }
        ksort($items);

        // Drop the keys and let the ArrayList handle the numbering, so $First, $Last and others work properly.
        return new ArrayList(array_values($items));
    }

    /**
     * @return DataObject|\SilverStripe\ORM\CMSPreviewable
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @param DataObject|CMSPreviewable $record
     * @return array template data
     */
    public static function get_for_record($record)
    {
        $html = '';
        $message = '';
        $navigator = new SilverStripeNavigator($record);
        $items = $navigator->getItems();
        foreach ($items as $item) {
            $text = $item->getHTML();
            if ($text) {
                $html .= $text;
            }
            $newMessage = $item->getMessage();
            if ($newMessage && $item->isActive()) {
                $message = $newMessage;
            }
        }

        return array(
            'items' => $html,
            'message' => $message
        );
    }
}
