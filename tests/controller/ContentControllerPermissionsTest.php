<?php
/**
 * @package cms
 * @subpackage tests
 */
class ContentControllerPermissionsTest extends FunctionalTest {
	
	protected $usesDatabase = true;
	
	protected $autoFollowRedirection = false;
	
	public function testCanViewStage() {
		// Create a new page
		$page = new Page();
		$page->URLSegment = 'testpage';
		$page->write();
		$page->publish('Stage', 'Live');

		// Add a stage-only version
		$page->Content = "Version2";
		$page->write();

		$response = $this->get('/testpage');
		$this->assertEquals($response->getStatusCode(), 200, "Doesn't require login for implicit live stage");
		
		$response = $this->get('/testpage/?stage=Live');
		$this->assertEquals($response->getStatusCode(), 200, "Doesn't require login for explicit live stage");

		try {
			$response = $this->get('/testpage/?stage=Stage');
		} catch(SS_HTTPResponse_Exception $responseException) {
			$response = $responseException->getResponse();
		}
		// should redirect to login
		$this->assertEquals($response->getStatusCode(), 302, 'Redirects to login page when not logged in for draft stage');
		$this->assertContains(
			Config::inst()->get('Security', 'login_url'),
			$response->getHeader('Location')
		);
		
		$this->logInWithPermission('CMS_ACCESS_CMSMain');
		
		$response = $this->get('/testpage/?stage=Stage');
		$this->assertEquals($response->getStatusCode(), 200, 'Doesnt redirect to login, but shows page for authenticated user');
	}
	
	
}
