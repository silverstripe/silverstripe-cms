<?php

namespace SilverStripe\CMS\Tests\Model;

use Page;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\RedirectorPageController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;

class RedirectorPageTest extends FunctionalTest
{
    protected static $fixture_file = 'RedirectorPageTest.yml';

    protected $autoFollowRedirection = false;

    public function setUp()
    {
        parent::setUp();
        Director::config()->update('alternate_base_url', 'http://www.mysite.com/');
        Config::modify()->set(RedirectorPageController::class, 'should_respond_404', false);
        // Ensure all pages are published
        /** @var Page $page */
        foreach (Page::get() as $page) {
            $page->publishSingle();
        }
    }

    public function testGoodRedirectors()
    {
        /* For good redirectors, the final destination URL will be returned */
        $this->assertEquals("http://www.google.com", $this->objFromFixture(RedirectorPage::class, 'goodexternal')->Link());
        $this->assertEquals("/redirection-dest/", $this->objFromFixture(RedirectorPage::class, 'goodinternal')->redirectionLink());
        $this->assertEquals("/redirection-dest/", $this->objFromFixture(RedirectorPage::class, 'goodinternal')->Link());
    }

    public function testEmptyRedirectors()
    {
        /* If a redirector page is misconfigured, then its link method will just return the usual URLSegment-generated value */
        $page1 = $this->objFromFixture(RedirectorPage::class, 'badexternal');
        $this->assertEquals('/bad-external/', $page1->Link());
        $response = $this->get($page1->Link());
        $this->assertEquals(200, $response->getStatusCode());

        /* An error message will be shown if you visit it */
        $content = $this->get(Director::makeRelative($page1->Link()))->getBody();
        $this->assertContains('message-setupWithoutRedirect', $content);

        /* This also applies for internal links */
        $page2 = $this->objFromFixture(RedirectorPage::class, 'badinternal');
        $this->assertEquals('/bad-internal/', $page2->Link());
        $response = $this->get(Director::makeRelative($page2->Link()));
        $content = $response->getBody();
        $this->assertContains('message-setupWithoutRedirect', $content);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReflexiveAndTransitiveInternalRedirectors()
    {
        /* Reflexive redirectors are those that point to themselves.  They should behave the same as an empty redirector */
        $page = $this->objFromFixture(RedirectorPage::class, 'reflexive');
        $this->assertEquals('/reflexive/', $page->Link());
        $content = $this->get(Director::makeRelative($page->Link()))->getBody();
        $this->assertContains('message-setupWithoutRedirect', $content);

        /* Transitive redirectors are those that point to another redirector page.  They should send people to the URLSegment
         * of the destination page - the middle-stop, so to speak.  That should redirect to the final destination */
        $page = $this->objFromFixture(RedirectorPage::class, 'transitive');
        $this->assertEquals('/good-internal/', $page->Link());

        $this->autoFollowRedirection = false;
        $response = $this->get(Director::makeRelative($page->Link()));
        $this->assertEquals(Director::absoluteURL('/redirection-dest/'), $response->getHeader("Location"));
    }

    public function testExternalURLGetsPrefixIfNotSet()
    {
        $page = $this->objFromFixture(RedirectorPage::class, 'externalnoprefix');
        $this->assertEquals($page->ExternalURL, 'http://google.com', 'onBeforeWrite has prefixed with http');
        $page->write();
        $this->assertEquals($page->ExternalURL, 'http://google.com', 'onBeforeWrite will not double prefix if written again!');
    }

    public function testAllowsProtocolRelative()
    {
        $noProtocol = new RedirectorPage(['ExternalURL' => 'mydomain.com']);
        $noProtocol->write();
        $this->assertEquals('http://mydomain.com', $noProtocol->ExternalURL);

        $protocolAbsolute = new RedirectorPage(['ExternalURL' => 'http://mydomain.com']);
        $protocolAbsolute->write();
        $this->assertEquals('http://mydomain.com', $protocolAbsolute->ExternalURL);

        $protocolRelative = new RedirectorPage(['ExternalURL' => '//mydomain.com']);
        $protocolRelative->write();
        $this->assertEquals('//mydomain.com', $protocolRelative->ExternalURL);
    }

    /**
     * Test that we can trigger a redirection before RedirectorPageController::init() is called
     */
    public function testRedirectRespectsFinishedResponse()
    {
        $page = $this->objFromFixture(RedirectorPage::class, 'goodinternal');
        RedirectorPageController::add_extension(RedirectorPageTest_RedirectExtension::class);

        $response = $this->get($page->regularLink());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://www.mysite.com/foo', $response->getHeader('Location'));

        RedirectorPageController::remove_extension(RedirectorPageTest_RedirectExtension::class);
    }

    public function testNoJSLinksAllowed()
    {
        $page = new RedirectorPage();
        $js = 'javascript:alert("hello world")';
        $page->ExternalURL = $js;
        $this->assertEquals($js, $page->ExternalURL);

        $page->write();
        $this->assertEmpty($page->ExternalURL);
    }

    public function testOnUnpublishedTargetPage()
    {
        // Check to make sure that redirector page is still published
        $page = $this->objFromFixture(RedirectorPage::class, 'goodinternal');
        $this->assertEquals('/redirection-dest/', $page->Link());
        $link = Director::makeRelative($page->Link());
        $response = $this->get($link);
        $this->assertEquals(200, $response->getStatusCode());

        // Check to make sure target page is still published
        $targetPage = $this->objFromFixture(Page::class, 'dest');
        $response = $this->get(Director::makeRelative($targetPage->Link()));
        $this->assertEquals(200, $response->getStatusCode());

        // Override to display 404
        Config::modify()->set(RedirectorPageController::class, 'should_respond_404', true);

        // Unpublish the target page of this redirector page.
        $this->assertTrue($targetPage->doUnpublish());
        $response = $this->get(Director::makeRelative($targetPage->Link()));
        $this->assertEquals(404, $response->getStatusCode());

        // Check redirector page should show 404 as well
        $response = $this->get($link);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
