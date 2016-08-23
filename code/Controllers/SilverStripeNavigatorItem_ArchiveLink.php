<?php
namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\Versioning\Versioned;

class SilverStripeNavigatorItem_ArchiveLink extends SilverStripeNavigatorItem
{
	/** @config */
	private static $priority = 40;

	public function getHTML()
	{
		$this->recordLink = $this->record->AbsoluteLink();
		return "<a class=\"ss-ui-button" . ($this->isActive() ? ' current' : '') . "\" href=\"$this->recordLink?archiveDate={$this->record->LastEdited}\" target=\"_blank\">" . _t('ContentController.ARCHIVEDSITE',
			'Preview version') . "</a>";
	}

	public function getTitle()
	{
		return _t('SilverStripeNavigator.ARCHIVED', 'Archived');
	}

	public function getMessage()
	{
		if ($date = Versioned::current_archived_date()) {
			/** @var DBDatetime $dateObj */
			$dateObj = DBField::create_field('Datetime', $date);
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"" . _t('ContentControl.NOTEWONTBESHOWN',
				'Note: this message will not be shown to your visitors') . "\">" . _t('ContentController.ARCHIVEDSITEFROM',
				'Archived site from') . "<br>" . $dateObj->Nice() . "</div>";
		}
	}

	public function getLink()
	{
		return $this->record->PreviewLink() . '?archiveDate=' . urlencode($this->record->LastEdited);
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
