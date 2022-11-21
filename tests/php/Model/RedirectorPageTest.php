<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\RedirectorPageController;
use SilverStripe\Control\Director;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Dev\FunctionalTest;

class RedirectorPageTest extends FunctionalTest
{
    protected static $fixture_file = 'RedirectorPageTest.yml';

    protected $autoFollowRedirection = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Set backend root to /ImageTest
        TestAssetStore::activate('FileTest');

        // Create a test files for each of the fixture references
        $fileIDs = $this->allFixtureIDs(File::class);

        foreach ($fileIDs as $fileID) {
            /** @var File $file */
            $file = File::get()->byId($fileID);
            $file->setFromString(str_repeat('x', 1000000), $file->getFilename());
            $file->publishSingle();
        }

        Director::config()->set('alternate_base_url', 'http://www.mysite.com/');

        // Ensure all pages are published
        /** @var SiteTree $page */
        foreach (SiteTree::get() as $page) {
            $page->publishSingle();
        }
    }

    public function testGoodRedirectors()
    {
        // For good redirectors, the final destination URL will be returned
        $this->assertEquals(
            "http://www.google.com",
            $this->objFromFixture(RedirectorPage::class, 'goodexternal')->Link()
        );
        $this->assertEquals(
            "/redirection-dest/",
            $this->objFromFixture(RedirectorPage::class, 'goodinternal')->redirectionLink()
        );
        $this->assertEquals(
            "/redirection-dest/",
            $this->objFromFixture(RedirectorPage::class, 'goodinternal')->Link()
        );
    }

    public function testEmptyRedirectors()
    {
        // If a redirector page is misconfigured, then its link method will just return the usual
        // URLSegment-generated value
        $page1 = $this->objFromFixture(RedirectorPage::class, 'badexternal');
        $this->assertEquals('/bad-external/', $page1->Link());

        // An error message will be shown if you visit it
        $content = $this->get(Director::makeRelative($page1->Link()))->getBody();
        $this->assertStringContainsString('message-setupWithoutRedirect', $content);

        // This also applies for internal links
        $page2 = $this->objFromFixture(RedirectorPage::class, 'badinternal');
        $this->assertEquals('/bad-internal/', $page2->Link());
        $content = $this->get(Director::makeRelative($page2->Link()))->getBody();
        $this->assertStringContainsString('message-setupWithoutRedirect', $content);
    }

    public function testReflexiveAndTransitiveInternalRedirectors()
    {
        // Reflexive redirectors are those that point to themselves.
        // They should behave the same as an empty redirector
        $page = $this->objFromFixture(RedirectorPage::class, 'reflexive');
        $this->assertEquals('/reflexive/', $page->Link());
        $content = $this->get(Director::makeRelative($page->Link()))->getBody();
        $this->assertStringContainsString('message-setupWithoutRedirect', $content);

        // Transitive redirectors are those that point to another redirector page.
        // They should send people to the URLSegment of the destination page - the middle-stop, so to speak.
        // That should redirect to the final destination
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
        $this->assertEquals(
            $page->ExternalURL,
            'http://google.com',
            'onBeforeWrite will not double prefix if written again!'
        );
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

    public function testFileRedirector()
    {
        $page = $this->objFromFixture(RedirectorPage::class, 'file');
        $this->assertStringContainsString("FileTest.txt", $page->Link());
    }
}
