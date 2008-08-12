<?php

class CMSMainTest extends SapphireTest {
	static $fixture_file = 'cms/tests/CMSMainTest.yml';
	
	/**
	 * @todo Test the results of a publication better
	 */
	function testPublish() {
		$session = new Session(array(
			'loggedInAs' => $this->idFromFixture('Member', 'admin')
		));
		
		$response = Director::test("admin/publishall", array('confirm' => 1), $session);
		$this->assertContains('Done: Published 4 pages', $response->getBody());

		$response = Director::test("admin/publishitems", array('csvIDs' => '1,2', 'ajax' => 1), $session);
		$this->assertContains('setNodeTitle(1, \'Page 1\');', $response->getBody());
		$this->assertContains('setNodeTitle(2, \'Page 2\');', $response->getBody());
		
		
		
		//$this->assertRegexp('/Done: Published 4 pages/', $response->getBody())
			
		/*
		$response = Director::test("admin/publishitems", array(
			'ID' => ''
			'Title' => ''
			'action_publish' => 'Save and publish',
		), $session);
		$this->assertRegexp('/Done: Published 4 pages/', $response->getBody())
		*/
	}
	
}