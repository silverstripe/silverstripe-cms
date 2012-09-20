<?php

/**
 * Tests link tracking to files and images.
 */
class FileLinkTrackingTest extends SapphireTest {
	static $fixture_file = "FileLinkTrackingTest.yml";
	
	public function setUp() {
		parent::setUp();
		$this->logInWithPermission('ADMIN');
		
		if(!file_exists(ASSETS_PATH)) mkdir(ASSETS_PATH);
		$fh = fopen(ASSETS_PATH . '/testscript-test-file.pdf', "w");
		fwrite($fh, str_repeat('x',1000000));
		fclose($fh);
	}

	public function tearDown() {
		parent::tearDown();
		$testFiles = array(
			'/testscript-test-file.pdf',
			'/renamed-test-file.pdf',
			'/renamed-test-file-second-time.pdf',
		);
		foreach($testFiles as $file) {
			if(file_exists(ASSETS_PATH . $file)) unlink(ASSETS_PATH . $file);
		}
	}
	
	public function testFileRenameUpdatesDraftAndPublishedPages() {
		$page = $this->objFromFixture('Page', 'page1');
		$this->assertTrue($page->doPublish());
		$this->assertContains('<img src="assets/testscript-test-file.pdf"',
			DB::query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = $page->ID")->value());
		
		$file = $this->objFromFixture('File', 'file1');
		$file->Name = 'renamed-test-file.pdf';
		$file->write();
		
		$this->assertContains('<img src="assets/renamed-test-file.pdf"',
			DB::query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());
		$this->assertContains('<img src="assets/renamed-test-file.pdf"',
			DB::query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = $page->ID")->value());
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
		$file = $this->objFromFixture('File', 'file1');
		$file->Name = 'renamed-test-file.pdf';
		$file->write();
		
		// Verify that the draft and publish virtual pages both have the corrected link
		$this->assertContains('<img src="assets/renamed-test-file.pdf"',
			DB::query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = $svp->ID")->value());
		$this->assertContains('<img src="assets/renamed-test-file.pdf"',
			DB::query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = $svp->ID")->value());
	}
	
	public function testLinkRewritingOnAPublishedPageDoesntMakeItEditedOnDraft() {
		// Publish the source page
		$page = $this->objFromFixture('Page', 'page1');
		$this->assertTrue($page->doPublish());
		$this->assertFalse($page->IsModifiedOnStage);

		// Rename the file
		$file = $this->objFromFixture('File', 'file1');
		$file->Name = 'renamed-test-file.pdf';
		$file->write();

		// Caching hack
		Versioned::prepopulate_versionnumber_cache('SiteTree', 'Stage', array($page->ID));
		Versioned::prepopulate_versionnumber_cache('SiteTree', 'Live', array($page->ID));

		// Confirm that the page hasn't gone green.
		$this->assertFalse($page->IsModifiedOnStage);
	}

	public function testTwoFileRenamesInARowWork() {
		$page = $this->objFromFixture('Page', 'page1');
		$this->assertTrue($page->doPublish());
		$this->assertContains('<img src="assets/testscript-test-file.pdf"',
			DB::query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = $page->ID")->value());

		// Rename the file twice
		$file = $this->objFromFixture('File', 'file1');
		$file->Name = 'renamed-test-file.pdf';
		$file->write();

		// TODO Workaround for bug in DataObject->getChangedFields(), which returns stale data,
		// and influences File->updateFilesystem()
		$file = DataObject::get_by_id('File', $file->ID);
		$file->Name = 'renamed-test-file-second-time.pdf';
		$file->write();
		
		// Confirm that the correct image is shown in both the draft and live site
		$this->assertContains('<img src="assets/renamed-test-file-second-time.pdf"',
			DB::query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());
		$this->assertContains('<img src="assets/renamed-test-file-second-time.pdf"',
			DB::query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = $page->ID")->value());
	}
}


