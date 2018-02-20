<?php
namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SiteTreeFutureState;

class SilverStripeNavigatorItem_StageLink extends SilverStripeNavigatorItem
{
    /** @config */
    private static $priority = 20;

    public function getHTML()
    {
        $draftPage = $this->getDraftPage();
        if (!$draftPage) {
            return null;
        }
        $linkClass = $this->isActive() ? 'class="current" ' : '';
        $linkTitle = _t('SilverStripe\\CMS\\Controllers\\ContentController.DRAFTSITE', 'Draft Site');
        $recordLink = Convert::raw2att(Controller::join_links($draftPage->AbsoluteLink(), "?stage=Stage"));
        return "<a {$linkClass} href=\"$recordLink\">$linkTitle</a>";
    }

    public function getTitle()
    {
        return _t(
            'SilverStripe\\CMS\\Controllers\\ContentController.DRAFT',
            'Draft',
            'Used for the Switch between draft and published view mode. Needs to be a short label'
        );
    }

    public function getMessage()
    {
        return "<div id=\"SilverStripeNavigatorMessage\" title=\"" . _t(
            'SilverStripe\\CMS\\Controllers\\ContentController.NOTEWONTBESHOWN',
            'Note: this message will not be shown to your visitors'
        ) . "\">" . _t(
            'SilverStripe\\CMS\\Controllers\\ContentController.DRAFTSITE',
            'Draft Site'
        ) . "</div>";
    }

    public function getLink()
    {
        $date = Versioned::current_archived_date();
        return Controller::join_links(
            $this->record->PreviewLink(),
            '?stage=Stage',
            $date ? '?archiveDate=' . $date : null
        );
    }

    public function canView($member = null)
    {
        /** @var Versioned|DataObject $record */
        $record = $this->record;
        return (
            $record->hasExtension(Versioned::class)
            && $record->hasStages()
            && $this->getDraftPage()
            // Don't follow redirects in preview, they break the CMS editing form
            && !($record instanceof RedirectorPage)
        );
    }

    public function isActive()
    {
        return (
            Versioned::get_stage() == 'Stage'
            && !(ClassInfo::exists('SiteTreeFutureState') && SiteTreeFutureState::get_future_datetime())
            && !$this->isArchived()
        );
    }

    protected function getDraftPage()
    {
        $baseClass = $this->record->baseClass();
        return Versioned::get_by_stage($baseClass, Versioned::DRAFT)->byID($this->record->ID);
    }
}
