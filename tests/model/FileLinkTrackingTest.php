<?php

/**
 * Tests link tracking to files and images.
 */
class FileLinkTrackingTest extends SapphireTest {
	protected static $fixture_file = "FileLinkTrackingTest.yml";
	
	public function setUp() {
		parent::setUp();
		AssetStoreTest_SpyStore::activate('FileLinkTrackingTest');
		$this->logInWithPermission('ADMIN');

		// Write file contents
		$files = File::get()->exclude('ClassName', 'Folder');
		foreach($files as $file) {
			$destPath = AssetStoreTest_SpyStore::getLocalPath($file);
			Filesystem::makeFolder(dirname($destPath));
			file_put_contents($destPath, str_repeat('x', 1000000));
		}

		// Since we can't hard-code IDs, manually inject image tracking shortcode
		$imageID = $this->idFromFixture('Image', 'file1');
		$page = $this->objFromFixture('Page', 'page1');
		$page->Content = sprintf(
			'<p><img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg" data-fileid="%d" /></p>',
			$imageID
		);
		$page->write();
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset();
		parent::tearDown();
	}
	
	public function testFileRenameUpdatesDraftAndPublishedPages() {
		$page = $this->objFromFixture('Page', 'page1');
		$this->assertTrue($page->doPublish());
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = ?", array($page->ID))->value()
		);
		
		$file = $this->objFromFixture('Image', 'file1');
		$file->Name = 'renamed-test-file.jpg';
		$file->write();
		
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = ?", array($page->ID))->value()
		);
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = ?", array($page->ID))->value()
		);
	}

	public function testFileLinkRewritingOnVirtualPages() {
		// Publish the source page
		$page = $this->objFromFixture('Page', 'page1');
		$this->assertTrue($page->doPublish());

		// Create a virtual page from it, and publish that
		$svp = new VirtualPage();
		$svp->CopyContentFromID = $page->ID;
		$svp->write();
		$svp->doPublish();
			
		// Rename the file
		$file = $this->objFromFixture('Image', 'file1');
		$file->Name = 'renamed-test-file.jpg';
		$file->write();
		
		// Verify that the draft and publish virtual pages both have the corrected link
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = ?", array($svp->ID))->value()
		);
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = ?", array($svp->ID))->value()
		);
	}
	
	public function testLinkRewritingOnAPublishedPageDoesntMakeItEditedOnDraft() {
		// Publish the source page
		$page = $this->objFromFixture('Page', 'page1');
		$this->assertTrue($page->doPublish());
		$this->assertFalse($page->getIsModifiedOnStage());

		// Rename the file
		$file = $this->objFromFixture('Image', 'file1');
		$file->Name = 'renamed-test-file.jpg';
		$file->write();

		// Caching hack
		Versioned::prepopulate_versionnumber_cache('SiteTree', 'Stage', array($page->ID));
		Versioned::prepopulate_versionnumber_cache('SiteTree', 'Live', array($page->ID));

		// Confirm that the page hasn't gone green.
		$this->assertFalse($page->getIsModifiedOnStage());
	}

	public function testTwoFileRenamesInARowWork() {
		$page = $this->objFromFixture('Page', 'page1');
		$this->assertTrue($page->doPublish());
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = ?", array($page->ID))->value());

		// Rename the file twice
		$file = $this->objFromFixture('Image', 'file1');
		$file->Name = 'renamed-test-file.jpg';
		$file->write();

		// TODO Workaround for bug in DataObject->getChangedFields(), which returns stale data,
		// and influences File->updateFilesystem()
		$file = DataObject::get_by_id('File', $file->ID);
		$file->Name = 'renamed-test-file-second-time.jpg';
		$file->write();
		
		// Confirm that the correct image is shown in both the draft and live site
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file-second-time.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = ?", array($page->ID))->value()
		);
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file-second-time.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = ?", array($page->ID))->value()
		);
	}
}


