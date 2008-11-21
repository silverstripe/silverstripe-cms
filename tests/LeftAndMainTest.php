<?php
/**
 * @package cms
 * @subpackage tests
 */
class LeftAndMainTest extends SapphireTest {
	static $fixture_file = 'cms/tests/CMSMainTest.yml';
	
	/**
	 * Check that all subclasses of leftandmain can be accessed
	 */
	public function testLeftAndMainSubclasses() {
		$session = new Session(array(
			'loggedInAs' => $this->idFromFixture('Member','admin')
		)); 

		// This controller stuff is needed because LeftAndMain::MainMenu() inspects the current user's permissions
		$controller = new Controller();
		$controller->setSession($session);
		$controller->pushCurrent();
		$menuItems = singleton('CMSMain')->MainMenu();
		$controller->popCurrent();
		
		$classes = ClassInfo::subclassesFor("LeftAndMain");
		foreach($menuItems as $menuItem) {
			$link = $menuItem->Link;
			if(preg_match('/^https?:\/\//',$link)) continue;

			$response = Director::test($link, null, $session);
			$this->assertType('HTTPResponse', $response, "$link should return a response object");
			$this->assertEquals(200, $response->getStatusCode(), "$link should return 200 status code");
			// Check that a HTML page has been returned
			$this->assertRegExp('/<html[^>]*>/i', $response->getBody(), "$link should contain <html> tag");
			$this->assertRegExp('/<head[^>]*>/i', $response->getBody(), "$link should contain <head> tag");
			$this->assertRegExp('/<body[^>]*>/i', $response->getBody(), "$link should contain <body> tag");
		}
	}
	
}

