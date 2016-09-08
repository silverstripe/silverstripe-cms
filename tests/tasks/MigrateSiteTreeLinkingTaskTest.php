<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Tasks\MigrateSiteTreeLinkingTask;
use SilverStripe\Dev\SapphireTest;



/**
 * @package cms
 * @subpackage tests
 */
class MigrateSiteTreeLinkingTaskTest extends SapphireTest {

	protected static $fixture_file = 'MigrateSiteTreeLinkingTaskTest.yml';

	protected static $use_draft_site = true;

	public function testLinkingMigration() {
		ob_start();

		$task = new MigrateSiteTreeLinkingTask();
		$task->run(null);

		$this->assertEquals (
			"Rewrote 9 link(s) on 5 page(s) to use shortcodes.\n",
			ob_get_contents(),
			'Rewritten links are correctly reported'
		);
		ob_end_clean();

		$homeID   = $this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'home');
		$aboutID  = $this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'about');
		$staffID  = $this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'staff');
		$actionID = $this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'action');
		$hashID   = $this->idFromFixture('SilverStripe\\CMS\\Model\\SiteTree', 'hash_link');

		$homeContent = sprintf (
			'<a href="[sitetree_link,id=%d]">About</a><a href="[sitetree_link,id=%d]">Staff</a><a href="http://silverstripe.org/">External Link</a><a name="anchor"></a>',
			$aboutID,
			$staffID
		);
		$aboutContent = sprintf (
			'<a href="[sitetree_link,id=%d]">Home</a><a href="[sitetree_link,id=%d]">Staff</a><a name="second-anchor"></a>',
			$homeID,
			$staffID
		);
		$staffContent = sprintf (
			'<a href="[sitetree_link,id=%d]">Home</a><a href="[sitetree_link,id=%d]">About</a>',
			$homeID,
			$aboutID
		);
		$actionContent = sprintf (
			'<a href="[sitetree_link,id=%d]SearchForm">Search Form</a>', $homeID
		);
		$hashLinkContent = sprintf (
			'<a href="[sitetree_link,id=%d]#anchor">Home</a><a href="[sitetree_link,id=%d]#second-anchor">About</a>',
			$homeID,
			$aboutID
		);

		$this->assertEquals (
			$homeContent,
			DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $homeID)->Content,
			'HTML URLSegment links are rewritten.'
		);
		$this->assertEquals (
			$aboutContent,
			DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $aboutID)->Content
		);
		$this->assertEquals (
			$staffContent,
			DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $staffID)->Content
		);
		$this->assertEquals (
			$actionContent,
			DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $actionID)->Content,
			'Links to actions on pages are rewritten correctly.'
		);
		$this->assertEquals (
			$hashLinkContent,
			DataObject::get_by_id('SilverStripe\\CMS\\Model\\SiteTree', $hashID)->Content,
			'Hash/anchor links are correctly handled.'
		);
	}

}
