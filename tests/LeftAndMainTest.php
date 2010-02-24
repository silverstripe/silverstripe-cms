<?php
/**
 * @package cms
 * @subpackage tests
 */
class LeftAndMainTest extends FunctionalTest {
	static $fixture_file = 'cms/tests/CMSMainTest.yml';
	
	function setUp() {
		parent::setUp();
		
		// @todo fix controller stack problems and re-activate
		//$this->autoFollowRedirection = false;
		CMSMenu::populate_menu();
	}
	
	/**
	 * Test that CMS versions can be interpreted appropriately
	 */
	public function testCMSVersion() {
		$l = new LeftAndMain();
		$this->assertEquals("2.4", $l->versionFromVersionFile(
			'$URL: http://svn.silverstripe.com/open/modules/cms/branches/2.4/silverstripe_version $'));
		$this->assertEquals("2.2.0", $l->versionFromVersionFile(
			'$URL: http://svn.silverstripe.com/open/modules/cms/tags/2.2.0/silverstripe_version $'));
		$this->assertEquals("trunk", $l->versionFromVersionFile(
			'$URL: http://svn.silverstripe.com/open/modules/cms/trunk/silverstripe_version $'));
		$this->assertEquals("2.4.0-alpha1", $l->versionFromVersionFile(
			'$URL: http://svn.silverstripe.com/open/modules/cms/tags/alpha/2.4.0-alpha1/silverstripe_version $'));
		$this->assertEquals("2.4.0-beta1", $l->versionFromVersionFile(
			'$URL: http://svn.silverstripe.com/open/modules/cms/tags/beta/2.4.0-beta1/silverstripe_version $'));
		$this->assertEquals("2.4.0-rc1", $l->versionFromVersionFile(
			'$URL: http://svn.silverstripe.com/open/modules/cms/tags/rc/2.4.0-rc1/silverstripe_version $'));
	}
	
	/**
	 * Check that all subclasses of leftandmain can be accessed
	 */
	public function testLeftAndMainSubclasses() {
		$adminuser = $this->objFromFixture('Member','admin');
		$this->session()->inst_set('loggedInAs', $adminuser->ID);
		
		$menuItems = singleton('CMSMain')->MainMenu();
		foreach($menuItems as $menuItem) {
			$link = $menuItem->Link;
			
			// don't test external links
			if(preg_match('/^https?:\/\//',$link)) continue;

			$response = $this->get($link);
			
			$this->assertType('SS_HTTPResponse', $response, "$link should return a response object");
			$this->assertEquals(200, $response->getStatusCode(), "$link should return 200 status code");
			// Check that a HTML page has been returned
			$this->assertRegExp('/<html[^>]*>/i', $response->getBody(), "$link should contain <html> tag");
			$this->assertRegExp('/<head[^>]*>/i', $response->getBody(), "$link should contain <head> tag");
			$this->assertRegExp('/<body[^>]*>/i', $response->getBody(), "$link should contain <body> tag");
		}
		
		$this->session()->inst_set('loggedInAs', null);

	}

	function testCanView() {
		$adminuser = $this->objFromFixture('Member', 'admin');
		$assetsonlyuser = $this->objFromFixture('Member', 'assetsonlyuser');
		$allcmssectionsuser = $this->objFromFixture('Member', 'allcmssectionsuser');
		
		// anonymous user
		$this->session()->inst_set('loggedInAs', null);
		$menuItems = singleton('LeftAndMain')->MainMenu();
		$this->assertEquals(
			$menuItems->column('Code'),
			array(),
			'Without valid login, members cant access any menu entries'
		);
		
		// restricted cms user
		$this->session()->inst_set('loggedInAs', $assetsonlyuser->ID);
		$menuItems = singleton('LeftAndMain')->MainMenu();
		$this->assertEquals(
			$menuItems->column('Code'),
			array('AssetAdmin','Help'),
			'Groups with limited access can only access the interfaces they have permissions for'
		);
		
		// all cms sections user
		$this->session()->inst_set('loggedInAs', $allcmssectionsuser->ID);
		$menuItems = singleton('LeftAndMain')->MainMenu();
		$requiredSections = array('CMSMain','AssetAdmin','CommentAdmin','SecurityAdmin','Help');
		$this->assertEquals(
			array_diff($requiredSections, $menuItems->column('Code')),
			array(),
			'Group with CMS_ACCESS_LeftAndMain permission can access all sections'
		);
		
		// admin
		$this->session()->inst_set('loggedInAs', $adminuser->ID);
		$menuItems = singleton('LeftAndMain')->MainMenu();
		$this->assertContains(
			'CMSMain',
			$menuItems->column('Code'),
			'Administrators can access CMS'
		);
		$this->assertContains(
			'AssetAdmin',
			$menuItems->column('Code'),
			'Administrators can access Assets'
		);
		
		$this->session()->inst_set('loggedInAs', null);
	}
	
}

