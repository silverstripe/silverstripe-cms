<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\CMS\Controllers\AssetAdmin;


/**
 * Tests {@see AssetAdmin}
 */
class AssetAdminTest extends SapphireTest {

	protected static $fixture_file = 'AssetAdminTest.yml';

	public function setUp() {
		parent::setUp();

		AssetStoreTest_SpyStore::activate('AssetAdminTest');

		if(!file_exists(ASSETS_PATH)) mkdir(ASSETS_PATH);

		// Create a test folders for each of the fixture references
		foreach(File::get()->filter('ClassName', 'Folder') as $folder) {
			/** @var Folder $folder */
			$folder->publishSingle();
		}

		// Create a test files for each of the fixture references
		$content = str_repeat('x',1000000);
		foreach(File::get()->exclude('ClassName', 'Folder') as $file) {
			/** @var File $file */
			$file->setFromString($content, $file->generateFilename());
			$file->publishSingle();
		}
	}

	public function tearDown() {
		parent::tearDown();

		// Remove the test files that we've created
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			if($file && file_exists(BASE_PATH."/$file->Filename")) unlink(BASE_PATH."/$file->Filename");
		}

		// Remove the test folders that we've crated
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			if($folder && file_exists(BASE_PATH."/$folder->Filename")) {
				Filesystem::removeFolder(BASE_PATH."/$folder->Filename");
			}
		}

		// Remove left over folders and any files that may exist
		if(file_exists(ASSETS_PATH.'/AssetAdminTest')) {
			Filesystem::removeFolder(ASSETS_PATH.'/AssetAdminTest');
		}
	}

	/**
	 * Mock a file search using AssetAdmin
	 *
	 * @param string $name
	 * @param string $from Created from date
	 * @param string $to Createi to date
	 * @param string $category
	 * @return SS_List
	 */
	protected function getResultsForSearch($name = '', $from = '', $to = '', $category = '') {
		$request = new SS_HTTPRequest(null, 'admin/assets/show', array(
			'q' => array(
				'Name' => $name,
				'CreatedFrom' => $from,
				'CreatedTo' => $to,
				'AppCategory' => $category
			),
			'action_doSearch' => 'Apply Filter'
		));
		$admin = new AssetAdmin();
		$admin->setRequest($request);
		return $admin->getList();
	}

	/**
	 * Tests filtering between date ranges
	 */
	public function testDateFromToLastSameDate() {
		$file1 = $this->objFromFixture('File', 'file1');
		$file2 = $this->objFromFixture('File', 'file2');

		// Force creation times
		$file1->Created = '2014-01-05 23:11:39';
		$file1->write();
		$file2->Created = '2014-01-06 12:00:00';
		$file2->write();

		// Mock searches for 4th Jan
		$results = $this->getResultsForSearch(null, '2014-01-04', '2014-01-04');
		$this->assertEmpty($results->column('Title'));

		// Mock searches for 5th Jan
		$results = $this->getResultsForSearch(null, '2014-01-05', '2014-01-05');
		$this->assertEquals(array('File1'), $results->column('Title'));

		// Mock searches for 5th-6th Jan
		$results = $this->getResultsForSearch(null, '2014-01-05', '2014-01-06');
		$this->assertEquals(array('File1', 'File2'), $results->sort('Title')->column('Title'));

		// Mock searches for 6th Jan
		$results = $this->getResultsForSearch(null, '2014-01-06', '2014-01-06');
		$this->assertEquals(array('File2'), $results->column('Title'));

		// Mock searches for 7th Jan
		$results = $this->getResultsForSearch(null, '2014-01-07', '2014-01-07');
		$this->assertEmpty($results->column('Title'));
	}
}
