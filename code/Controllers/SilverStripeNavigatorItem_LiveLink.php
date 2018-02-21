<?php
namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class SilverStripeNavigatorItem_LiveLink extends SilverStripeNavigatorItem
{
    /** @config */
    private static $priority = 30;

    public function getHTML()
    {
        $livePage = $this->getLivePage();
        if (!$livePage) {
            return null;
        }

        $linkClass = $this->isActive() ? 'class="current" ' : '';
        $linkTitle = _t('SilverStripe\\CMS\\Controllers\\ContentController.PUBLISHEDSITE', 'Published Site');
        $recordLink = Convert::raw2att(Controller::join_links($livePage->AbsoluteLink(), "?stage=Live"));
        return "<a {$linkClass} href=\"$recordLink\">$linkTitle</a>";
    }

    public function getTitle()
    {
        return _t(
            'SilverStripe\\CMS\\Controllers\\ContentController.PUBLISHED',
            'Published',
            'Used for the Switch between draft and published view mode. Needs to be a short label'
        );
    }

    public function getMessage()
    {
        return "<div id=\"SilverStripeNavigatorMessage\" title=\"" . _t(
            'SilverStripe\\CMS\\Controllers\\ContentController.NOTEWONTBESHOWN',
            'Note: this message will not be shown to your visitors'
        ) . "\">" . _t(
            'SilverStripe\\CMS\\Controllers\\ContentController.PUBLISHEDSITE',
            'Published Site'
        ) . "</div>";
    }

    public function getLink()
    {
        return Controller::join_links($this->record->PreviewLink(), '?stage=Live');
    }

    public function canView($member = null)
    {
        /** @var Versioned|DataObject $record */
        $record = $this->record;
        return (
            $record->hasExtension(Versioned::class)
            && $record->hasStages()
            && $this->getLivePage()
            // Don't follow redirects in preview, they break the CMS editing form
            && !($this->record instanceof RedirectorPage)
        );
    }

    public function isActive()
    {
        return (
            (!Versioned::get_stage() || Versioned::get_stage() == 'Live')
            && !$this->isArchived()
        );
    }

    protected function getLivePage()
    {
        $baseClass = $this->record->baseClass();
        return Versioned::get_by_stage($baseClass, Versioned::LIVE)->byID($this->record->ID);
    }
}
