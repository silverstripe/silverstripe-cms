<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\RedirectorPageController;
use SilverStripe\Control\Director;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use PHPUnit\Framework\Attributes\DataProvider;

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
            "/redirection-dest",
            $this->objFromFixture(RedirectorPage::class, 'goodinternal')->redirectionLink()
        );
        $this->assertEquals(
            "/redirection-dest",
            $this->objFromFixture(RedirectorPage::class, 'goodinternal')->Link()
        );
    }

    public static function provideEmptyRedirectors()
    {
        return [
            'use 200' => [
                'use404' => false,
            ],
            'use 404' => [
                'use404' => true,
            ],
        ];
    }

    #[DataProvider('provideEmptyRedirectors')]
    public function testEmptyRedirectors(bool $use404)
    {
        Config::modify()->set(RedirectorPageController::class, 'missing_redirect_is_404', $use404);
        // If a redirector page is misconfigured, then its link method will just return the usual
        // URLSegment-generated value
        $page1 = $this->objFromFixture(RedirectorPage::class, 'badexternal');
        $this->assertEquals('/bad-external', $page1->Link());
        $response = $this->get($page1->Link());
        $this->assertEquals(200, $response->getStatusCode());

        // An error message will be shown if you visit it
        $content = $this->get(Director::makeRelative($page1->Link()))->getBody();
        $this->assertStringContainsString('message-setupWithoutRedirect', $content);

        // This also applies for internal links
        $page2 = $this->objFromFixture(RedirectorPage::class, 'badinternal');
        $this->assertEquals('/bad-internal', $page2->Link());
        $response = $this->get(Director::makeRelative($page2->Link()));
        $content = $response->getBody();
        if ($use404) {
            $this->assertNull($response->getBody());
        } else {
            $this->assertStringContainsString('message-setupWithoutRedirect', $content);
        }
    }

    public function testReflexiveAndTransitiveInternalRedirectors()
    {
        // Reflexive redirectors are those that point to themselves.
        // They should behave the same as an empty redirector
        $page = $this->objFromFixture(RedirectorPage::class, 'reflexive');
        $this->assertEquals('/reflexive', $page->Link());
        $content = $this->get(Director::makeRelative($page->Link()))->getBody();
        $this->assertStringContainsString('message-setupWithoutRedirect', $content);

        // Transitive redirectors are those that point to another redirector page.
        // They should send people to the URLSegment of the destination page - the middle-stop, so to speak.
        // That should redirect to the final destination
        $page = $this->objFromFixture(RedirectorPage::class, 'transitive');
        $this->assertEquals('/good-internal', $page->Link());

        $this->autoFollowRedirection = false;
        $response = $this->get(Director::makeRelative($page->Link()));
        $this->assertEquals(Director::absoluteURL('/redirection-dest'), $response->getHeader("Location"));
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

    public function testFileRedirector()
    {
        $page = $this->objFromFixture(RedirectorPage::class, 'file');
        $this->assertStringContainsString("FileTest.txt", $page->Link());
    }

    public static function provideUnpublishedTarget()
    {
        return [
            'use 200 with sitetree' => [
                'use404' => false,
                'isFile' => false,
            ],
            'use 404 with sitetree' => [
                'use404' => true,
                'isFile' => false,
            ],
            'use 200 with file' => [
                'use404' => false,
                'isFile' => true,
            ],
            'use 404 with file' => [
                'use404' => true,
                'isFile' => true,
            ],
        ];
    }

    #[DataProvider('provideUnpublishedTarget')]
    public function testUnpublishedTarget(bool $use404, bool $isFile)
    {
        Config::modify()->set(RedirectorPageController::class, 'missing_redirect_is_404', $use404);
        $redirectorPage = $this->objFromFixture(RedirectorPage::class, $isFile ? 'file' : 'goodinternal');
        $targetModel = $isFile ? $redirectorPage->LinkToFile() : $redirectorPage->LinkTo();
        $targetModel->publishSingle();
        $redirectorPage->publishSingle();
        $this->assertEquals(Controller::normaliseTrailingSlash($isFile ? '/filedirector' : '/good-internal'), $redirectorPage->regularLink());
        $redirectorPageLink = Director::makeRelative($redirectorPage->regularLink());

        // redirector page should give 301 (redirection) status code
        $response = $this->get($redirectorPageLink);
        $this->assertEquals(301, $response->getStatusCode());

        // Unpublish the target model of this redirector page.
        $targetModel->doUnpublish();

        // redirector page should give a 404 or a 200 based on config when there's no page to redirect to
        $response = $this->get($redirectorPageLink);
        $this->assertEquals($use404 ? 404 : 200, $response->getStatusCode());
    }
}
