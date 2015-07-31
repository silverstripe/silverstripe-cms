<?php

class RedirectorPageTest extends FunctionalTest {
	protected static $fixture_file = 'RedirectorPageTest.yml';
	protected static $use_draft_site = true;
	protected $autoFollowRedirection = false;
	
	public function testGoodRedirectors() {
		/* For good redirectors, the final destination URL will be returned */
		$this->assertEquals("http://www.google.com", $this->objFromFixture('RedirectorPage','goodexternal')->Link());
		$this->assertEquals(Director::baseURL() . "redirection-dest/", $this->objFromFixture('RedirectorPage','goodinternal')->redirectionLink());
		$this->assertEquals(Director::baseURL() . "redirection-dest/", $this->objFromFixture('RedirectorPage','goodinternal')->Link());
	}

	public function testEmptyRedirectors() {
		/* If a redirector page is misconfigured, then its link method will just return the usual URLSegment-generated value */
		$page1 = $this->objFromFixture('RedirectorPage','badexternal');
		$this->assertEquals(Director::baseURL() . 'bad-external/', $page1->Link());

		/* An error message will be shown if you visit it */
		$content = $this->get(Director::makeRelative($page1->Link()))->getBody();
		$this->assertContains('message-setupWithoutRedirect', $content);

		/* This also applies for internal links */
		$page2 = $this->objFromFixture('RedirectorPage','badinternal');
		$this->assertEquals(Director::baseURL() . 'bad-internal/', $page2->Link());
		$content = $this->get(Director::makeRelative($page2->Link()))->getBody();
		$this->assertContains('message-setupWithoutRedirect', $content);
	}

	public function testReflexiveAndTransitiveInternalRedirectors() {
		/* Reflexive redirectors are those that point to themselves.  They should behave the same as an empty redirector */
		$page = $this->objFromFixture('RedirectorPage','reflexive');
		$this->assertEquals(Director::baseURL() . 'reflexive/', $page->Link());
		$content = $this->get(Director::makeRelative($page->Link()))->getBody();
		$this->assertContains('message-setupWithoutRedirect', $content);

		/* Transitive redirectors are those that point to another redirector page.  They should send people to the URLSegment
		 * of the destination page - the middle-stop, so to speak.  That should redirect to the final destination */
		$page = $this->objFromFixture('RedirectorPage','transitive');
		$this->assertEquals(Director::baseURL() . 'good-internal/', $page->Link());
		
		$this->autoFollowRedirection = false;
		$response = $this->get(Director::makeRelative($page->Link()));
		$this->assertEquals(Director::baseURL() . "redirection-dest/", $response->getHeader("Location"));
	}

	public function testExternalURLGetsPrefixIfNotSet() {
		$page = $this->objFromFixture('RedirectorPage', 'externalnoprefix');
		$this->assertEquals($page->ExternalURL, 'http://google.com', 'onBeforeWrite has prefixed with http');
		$page->write();
		$this->assertEquals($page->ExternalURL, 'http://google.com', 'onBeforeWrite will not double prefix if written again!');
	}

	public function testAllowsProtocolRelative() {
		$noProtocol = new RedirectorPage(array('ExternalURL' => 'mydomain.com'));
		$noProtocol->write();
		$this->assertEquals('http://mydomain.com', $noProtocol->ExternalURL);

		$protocolAbsolute = new RedirectorPage(array('ExternalURL' => 'http://mydomain.com'));
		$protocolAbsolute->write();
		$this->assertEquals('http://mydomain.com', $protocolAbsolute->ExternalURL);

		$protocolRelative = new RedirectorPage(array('ExternalURL' => '//mydomain.com'));
		$protocolRelative->write();
		$this->assertEquals('//mydomain.com', $protocolRelative->ExternalURL);
	}

	/**
	 * Test that we can trigger a redirection before RedirectorPage_Controller::init() is called
	 */
	public function testRedirectRespectsFinishedResponse() {
		$page = $this->objFromFixture('RedirectorPage', 'goodinternal');
		RedirectorPage_Controller::add_extension('RedirectorPageTest_RedirectExtension');

		$response = $this->get($page->regularLink());
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertEquals('/foo', $response->getHeader('Location'));

		RedirectorPage_Controller::remove_extension('RedirectorPageTest_RedirectExtension');
	}

}

class RedirectorPageTest_RedirectExtension extends Extension implements TestOnly {

	public function onBeforeInit() {
		$this->owner->redirect('/foo');
	}

}
