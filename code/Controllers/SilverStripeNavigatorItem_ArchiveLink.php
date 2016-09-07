<?php
namespace SilverStripe\CMS\Controllers;

use Controller;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_ArchiveLink extends SilverStripeNavigatorItem
{
	/** @config */
	private static $priority = 40;

	public function getHTML()
	{
        $linkClass = $this->isActive() ? 'ss-ui-button current' : 'ss-ui-button';
        $linkTitle = _t('ContentController.ARCHIVEDSITE', 'Preview version');
        $recordLink = Convert::raw2att(Controller::join_links(
            $this->record->AbsoluteLink(),
            '?archiveDate=' . urlencode($this->record->LastEdited)
        ));
        return "<a class=\"{$linkClass}\" href=\"$recordLink\" target=\"_blank\">$linkTitle</a>";
	}

	public function getTitle()
	{
		return _t('SilverStripeNavigator.ARCHIVED', 'Archived');
	}

	public function getMessage()
	{
        $date = Versioned::current_archived_date();
        if (empty($date)) {
            return null;
        }
        /** @var SS_Datetime $dateObj */
        $dateObj = DBField::create_field('Datetime', $date);
        $title = _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors');
        return "<div id=\"SilverStripeNavigatorMessage\" title=\"{$title}\">"
            . _t('ContentController.ARCHIVEDSITEFROM', 'Archived site from')
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
		return (
			$this->record->hasExtension('SilverStripe\ORM\Versioning\Versioned')
			&& $this->isArchived()
			// Don't follow redirects in preview, they break the CMS editing form
			&& !($this->record instanceof RedirectorPage)
		);
	}

	public function isActive()
	{
		return $this->isArchived();
	}
}
