<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;
use Page;

class FileLinkTrackingTest extends SapphireTest
{
    protected static $fixture_file = "FileLinkTrackingTest.yml";

    public function setUp()
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);

        TestAssetStore::activate('FileLinkTrackingTest');
        $this->logInWithPermission('ADMIN');

        // Write file contents
        $files = File::get()->exclude('ClassName', Folder::class);
        foreach ($files as $file) {
            $destPath = TestAssetStore::getLocalPath($file);
            Filesystem::makeFolder(dirname($destPath));
            file_put_contents($destPath, str_repeat('x', 1000000));
            // Ensure files are published, thus have public urls
            $file->publishRecursive();
        }

        // Since we can't hard-code IDs, manually inject image tracking shortcode
        $imageID = $this->idFromFixture(Image::class, 'file1');
        $page = $this->objFromFixture('Page', 'page1');
        $page->Content = sprintf(
            '<p>[image src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg" id="%d"]</p>',
            $imageID
        );
        $page->write();
    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    /**
     * Test uses global state through Versioned::set_reading_mode() since
     * the shortcode parser doesn't pass along the underlying DataObject
     * context, hence we can't call getSourceQueryParams().
     */
    public function testFileRenameUpdatesDraftAndPublishedPages()
    {
        $page = $this->objFromFixture('Page', 'page1');
        $page->publishRecursive();

        // Live and stage pages both have link to public file
        Versioned::set_stage(Versioned::DRAFT);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );
        Versioned::set_stage(Versioned::LIVE);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );

        Versioned::set_stage(Versioned::DRAFT);
        $file = $this->objFromFixture(Image::class, 'file1');
        $file->Name = 'renamed-test-file.jpg';
        $file->write();

        // Staged record now points to secure URL of renamed file, live record remains unchanged
        // Note that the "secure" url doesn't have the "FileLinkTrackingTest" component because
        // the mocked test location disappears for secure files.
        Versioned::set_stage(Versioned::DRAFT);
        $this->assertContains(
            '<img src="/assets/55b443b601/renamed-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );
        Versioned::set_stage(Versioned::LIVE);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );

        // Publishing the file should result in a direct public link (indicated by "FileLinkTrackingTest")
        // Although the old live page will still point to the old record.
        // @todo - Ensure shortcodes are used with all images to prevent live records having broken links
        $file->publishRecursive();
        Versioned::set_stage(Versioned::DRAFT);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );
        Versioned::set_stage(Versioned::LIVE);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );

        // Publishing the page after publishing the asset should retain linking
        $page->publishRecursive();
        Versioned::set_stage(Versioned::DRAFT);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );
        Versioned::set_stage(Versioned::LIVE);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );
    }

    public function testFileLinkRewritingOnVirtualPages()
    {
        // Publish the source page
        $page = $this->objFromFixture('Page', 'page1');
        $this->assertTrue($page->publishRecursive());

        // Create a virtual page from it, and publish that
        $svp = new VirtualPage();
        $svp->CopyContentFromID = $page->ID;
        $svp->write();
        $svp->publishRecursive();

        // Rename the file
        $file = $this->objFromFixture(Image::class, 'file1');
        $file->Name = 'renamed-test-file.jpg';
        $file->write();

        // Verify that the draft virtual pages have the correct content
        Versioned::set_stage(Versioned::DRAFT);
        $this->assertContains(
            '<img src="/assets/55b443b601/renamed-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );

        // Publishing both file and page will update the live record
        $file->publishRecursive();
        $page->publishRecursive();

        Versioned::set_stage(Versioned::LIVE);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );
    }

    public function testLinkRewritingOnAPublishedPageDoesntMakeItEditedOnDraft()
    {
        // Publish the source page
        /** @var Page $page */
        $page = $this->objFromFixture('Page', 'page1');
        $this->assertTrue($page->publishRecursive());
        $this->assertFalse($page->isModifiedOnDraft());

        // Rename the file
        $file = $this->objFromFixture(Image::class, 'file1');
        $file->Name = 'renamed-test-file.jpg';
        $file->write();

        // Confirm that the page hasn't gone green.
        $this->assertFalse($page->isModifiedOnDraft());
    }

    public function testTwoFileRenamesInARowWork()
    {
        $page = $this->objFromFixture('Page', 'page1');
        $this->assertTrue($page->publishRecursive());

        Versioned::set_stage(Versioned::LIVE);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );

        // Rename the file twice
        Versioned::set_stage(Versioned::DRAFT);
        $file = $this->objFromFixture(Image::class, 'file1');
        $file->Name = 'renamed-test-file.jpg';
        $file->write();

        // TODO Workaround for bug in DataObject->getChangedFields(), which returns stale data,
        // and influences File->updateFilesystem()
        $file = DataObject::get_by_id('SilverStripe\\Assets\\File', $file->ID);
        $file->Name = 'renamed-test-file-second-time.jpg';
        $file->write();
        $file->publishRecursive();

        // Confirm that the correct image is shown in both the draft and live site
        Versioned::set_stage(Versioned::DRAFT);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file-second-time.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );

        // Publishing this record also updates live record
        $page->publishRecursive();
        Versioned::set_stage(Versioned::LIVE);
        $this->assertContains(
            '<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file-second-time.jpg"',
            Page::get()->byID($page->ID)->dbObject('Content')->forTemplate()
        );
    }
}
