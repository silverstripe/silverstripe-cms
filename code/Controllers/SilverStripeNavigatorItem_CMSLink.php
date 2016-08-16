<?php
namespace SilverStripe\CMS\Controllers;

use Controller;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Model\RedirectorPage;

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_CMSLink extends SilverStripeNavigatorItem
{
	/** @config */
	private static $priority = 10;

	public function getHTML()
	{
		return sprintf(
			'<a href="%s">%s</a>',
			$this->record->CMSEditLink(),
			_t('ContentController.CMS', 'CMS')
		);
	}

	public function getTitle()
	{
		return _t('ContentController.CMS', 'CMS', 'Used in navigation. Should be a short label');
	}

	public function getLink()
	{
		return $this->record->CMSEditLink();
	}

	public function isActive()
	{
		return (Controller::curr() instanceof LeftAndMain);
	}

	public function canView($member = null)
	{
		return (
			// Don't show in CMS
			!(Controller::curr() instanceof LeftAndMain)
			// Don't follow redirects in preview, they break the CMS editing form
			&& !($this->record instanceof RedirectorPage)
		);
	}

}
