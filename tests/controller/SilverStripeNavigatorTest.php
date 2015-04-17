<?php
/**
 * @package cms
 * @subpackage tests
 */

class SilverStripeNavigatorTest extends SapphireTest {
	
	protected static $fixture_file = 'cms/tests/controller/CMSMainTest.yml';
	
	public function testGetItems() {
		$page = $this->objFromFixture('Page', 'page1');
		$navigator = new SilverStripeNavigator($page);
		
		$items = $navigator->getItems();
		$classes = array_map('get_class', $items->toArray());
		$this->assertContains('SilverStripeNavigatorItem_StageLink', $classes,
			'Adds default classes'
		);
		
		$this->assertContains('SilverStripeNavigatorTest_TestItem', $classes,
			'Autodiscovers new classes'
		);
	}
	
	public function testCanView() {
		$page = $this->objFromFixture('Page', 'page1');
		$admin = $this->objFromFixture('Member', 'admin');
		$author = $this->objFromFixture('Member', 'assetsonlyuser');
		$navigator = new SilverStripeNavigator($page);
		
		// TODO Shouldn't be necessary but SapphireTest logs in as ADMIN by default
		$this->logInWithPermission('CMS_ACCESS_AssetAdmin');
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
}

class SilverStripeNavigatorTest_ProtectedTestItem extends SilverStripeNavigatorItem implements TestOnly {
	public function canView($member = null) {
		if(!$member) $member = Member::currentUser();
		return Permission::checkMember($member, 'ADMIN');
	}
}
