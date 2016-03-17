<?php

/**
 * Tests link tracking to files and images.
 */
class FileLinkTrackingTest extends SapphireTest {
	protected static $fixture_file = "FileLinkTrackingTest.yml";

	public function setUp() {
		parent::setUp();

		Versioned::set_stage(Versioned::DRAFT);

		AssetStoreTest_SpyStore::activate('FileLinkTrackingTest');
		$this->logInWithPermission('ADMIN');

		// Write file contents
		$files = File::get()->exclude('ClassName', 'Folder');
		foreach($files as $file) {
			$destPath = AssetStoreTest_SpyStore::getLocalPath($file);
			Filesystem::makeFolder(dirname($destPath));
			file_put_contents($destPath, str_repeat('x', 1000000));
			// Ensure files are published, thus have public urls
			$file->doPublish();
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
		$page->doPublish();

		// Live and stage pages both have link to public file
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = ?", array($page->ID))->value()
		);
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = ?", array($page->ID))->value()
		);

		$file = $this->objFromFixture('Image', 'file1');
		$file->Name = 'renamed-test-file.jpg';
		$file->write();

		// Staged record now points to secure URL of renamed file, live record remains unchanged
		// Note that the "secure" url doesn't have the "FileLinkTrackingTest" component because
		// the mocked test location disappears for secure files.
		$this->assertContains(
			'<img src="/assets/55b443b601/renamed-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = ?", array($page->ID))->value()
		);
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = ?", array($page->ID))->value()
		);

		// Publishing the file should result in a direct public link (indicated by "FileLinkTrackingTest")
		// Although the old live page will still point to the old record.
		// @todo - Ensure shortcodes are used with all images to prevent live records having broken links
		$file->doPublish();
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = ?", array($page->ID))->value()
		);
		$this->assertContains(
			// Note: Broken link until shortcode-enabled
			'<img src="/assets/FileLinkTrackingTest/55b443b601/testscript-test-file.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = ?", array($page->ID))->value()
		);

		// Publishing the page after publishing the asset will resolve any link issues
		$page->doPublish();
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

		// Verify that the draft virtual pages have the correct content
		$svp = Versioned::get_by_stage('VirtualPage', Versioned::DRAFT)->byID($svp->ID);
		$this->assertContains(
			'<img src="/assets/55b443b601/renamed-test-file.jpg"',
			$svp->Content
		);

		// Publishing both file and page will update the live record
		$file->doPublish();
		$page->doPublish();

		$svp = Versioned::get_by_stage('VirtualPage', Versioned::LIVE)->byID($svp->ID);
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file.jpg"',
			$svp->Content
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
		$file->doPublish();

		// Confirm that the correct image is shown in both the draft and live site
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file-second-time.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = ?", array($page->ID))->value()
		);

		// Publishing this record also updates live record
		$page->doPublish();
		$this->assertContains(
			'<img src="/assets/FileLinkTrackingTest/55b443b601/renamed-test-file-second-time.jpg"',
			DB::prepared_query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = ?", array($page->ID))->value()
		);
	}
}


