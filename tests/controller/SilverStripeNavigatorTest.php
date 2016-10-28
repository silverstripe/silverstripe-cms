<?php

use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\CMS\Controllers\SilverStripeNavigator;
use SilverStripe\CMS\Controllers\SilverStripeNavigatorItem;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;


/**
 * @package cms
 * @subpackage tests
 */

class SilverStripeNavigatorTest extends SapphireTest {

	protected static $fixture_file = 'CMSMainTest.yml';

	public function testGetItems() {
		$page = $this->objFromFixture('Page', 'page1');
		$navigator = new SilverStripeNavigator($page);

		$items = $navigator->getItems();
		$classes = array_map('get_class', $items->toArray());
		$this->assertContains('SilverStripe\\CMS\\Controllers\\SilverStripeNavigatorItem_StageLink', $classes,
			'Adds default classes'
		);

		$this->assertContains('SilverStripeNavigatorTest_TestItem', $classes,
			'Autodiscovers new classes'
		);
	}

	public function testCanView() {
		$page = $this->objFromFixture('Page', 'page1');
		$admin = $this->objFromFixture('SilverStripe\\Security\\Member', 'admin');
		$navigator = new SilverStripeNavigator($page);

		// TODO Shouldn't be necessary but SapphireTest logs in as ADMIN by default
		$this->logInWithPermission('CMS_ACCESS_CMSMain');
		$items = $navigator->getItems();
		$classes = array_map('get_class', $items->toArray());
		$this->assertNotContains('SilverStripeNavigatorTest_ProtectedTestItem', $classes);

		$this->logInWithPermission('ADMIN');
		$items = $navigator->getItems();
		$classes = array_map('get_class', $items->toArray());
		$this->assertContains('SilverStripeNavigatorTest_ProtectedTestItem', $classes);
	}

}

class SilverStripeNavigatorTest_TestItem extends SilverStripeNavigatorItem implements TestOnly {
	public function getTitle() {
		return self::class;
	}
	public function getHTML() {
		return null;
	}
}

class SilverStripeNavigatorTest_ProtectedTestItem extends SilverStripeNavigatorItem implements TestOnly {

	public function getTitle() {
		return self::class;
	}

	public function getHTML() {
		return null;
	}

	public function canView($member = null) {
		if(!$member) $member = Member::currentUser();
		return Permission::checkMember($member, 'ADMIN');
	}
}
