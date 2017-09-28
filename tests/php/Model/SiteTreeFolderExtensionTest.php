<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;
use SilverStripe\CMS\Tests\Model\SiteTreeFolderExtensionTest\PageWithFile;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

class SiteTreeFolderExtensionTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        PageWithFile::class,
    ];

    protected static $fixture_file = 'SiteTreeFolderExtensionTest.yml';

    public function setUp()
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);

        TestAssetStore::activate('SiteTreeFolderExtensionTest');
        $this->logInWithPermission('ADMIN');

        // Since we can't hard-code IDs, manually inject image tracking shortcode
        $imageID = $this->idFromFixture(Image::class, 'image1');
        $page = $this->objFromFixture(PageWithFile::class, 'page1');
        $page->Content = sprintf(
            '<p>[image id="%d"]</p>',
            $imageID
        );
        $page->write();
    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testFindsFiles()
    {
        /** @var PageWithFile $page */
        $page = $this->objFromFixture(PageWithFile::class, 'page1');
        $query = $page->getUnusedFilesListFilter();
        $this->assertContains('"ID" NOT IN', $query);
        $this->assertContains('"ClassName" IN (', $query);

        $files = File::get()->where($query);
        $this->assertDOSEquals(
            [
                ['Name' => 'file2.txt'],
                ['Name' => 'image2.jpg'],
            ],
            $files
        );
    }
}
