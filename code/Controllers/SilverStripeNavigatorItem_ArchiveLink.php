<?php
namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Versioned\Versioned;

class SilverStripeNavigatorItem_ArchiveLink extends SilverStripeNavigatorItem
{
    /** @config */
    private static $priority = 40;

    public function getHTML()
    {
        $linkClass = $this->isActive() ? 'ss-ui-button current' : 'ss-ui-button';
        $linkTitle = _t('SilverStripe\\CMS\\Controllers\\ContentController.ARCHIVEDSITE', 'Preview version');
        $recordLink = Convert::raw2att(Controller::join_links(
            $this->record->AbsoluteLink(),
            '?archiveDate=' . urlencode($this->record->LastEdited)
        ));
        return "<a class=\"{$linkClass}\" href=\"$recordLink\" target=\"_blank\">$linkTitle</a>";
    }

    public function getTitle()
    {
        return _t('SilverStripe\\CMS\\Controllers\\SilverStripeNavigator.ARCHIVED', 'Archived');
    }

    public function getMessage()
    {
        $date = Versioned::current_archived_date();
        if (empty($date)) {
            return null;
        }
        /** @var DBDatetime $dateObj */
        $dateObj = DBField::create_field('Datetime', $date);
        $title = _t('SilverStripe\\CMS\\Controllers\\ContentController.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors');
        return "<div id=\"SilverStripeNavigatorMessage\" title=\"{$title}\">"
            . _t('SilverStripe\\CMS\\Controllers\\ContentController.ARCHIVEDSITEFROM', 'Archived site from')
            . "<br />" . $dateObj->Nice() . "</div>";
    }

    public function getLink()
    {
        return Controller::join_links(
            $this->record->PreviewLink(),
            '?archiveDate=' . urlencode($this->record->LastEdited)
        );
    }

    public function canView($member = null)
    {
        /** @var Versioned|DataObject $record */
        $record = $this->record;
        return (
            $record->hasExtension(Versioned::class)
            && $record->hasStages()
            && $this->isArchived()
            // Don't follow redirects in preview, they break the CMS editing form
            && !($record instanceof RedirectorPage)
        );
    }

    public function isActive()
    {
        return $this->isArchived();
    }
}
