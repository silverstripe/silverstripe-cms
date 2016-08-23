<?php
namespace SilverStripe\CMS\Controllers;

use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\Versioning\Versioned;

class SilverStripeNavigatorItem_LiveLink extends SilverStripeNavigatorItem
{
	/** @config */
	private static $priority = 30;

	public function getHTML()
	{
		$livePage = $this->getLivePage();
		if ($livePage) {
			$this->recordLink = Controller::join_links($livePage->AbsoluteLink(), "?stage=Live");
			return "<a " . ($this->isActive() ? 'class="current" ' : '') . "href=\"$this->recordLink\">" . _t('ContentController.PUBLISHEDSITE',
				'Published Site') . "</a>";
		}
	}

	public function getTitle()
	{
		return _t('ContentController.PUBLISHED', 'Published',
			'Used for the Switch between draft and published view mode. Needs to be a short label');
	}

	public function getMessage()
	{
		return "<div id=\"SilverStripeNavigatorMessage\" title=\"" . _t('ContentControl.NOTEWONTBESHOWN',
			'Note: this message will not be shown to your visitors') . "\">" . _t('ContentController.PUBLISHEDSITE',
			'Published Site') . "</div>";
	}

	public function getLink()
	{
		return Controller::join_links($this->record->PreviewLink(), '?stage=Live');
	}

	public function canView($member = null)
	{
		return (
			$this->record->hasExtension('SilverStripe\ORM\Versioning\Versioned')
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
