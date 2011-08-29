<?php

/**
 * @package cms
 * @subpackage tests
 */

class CMSPageHistoryControllerTest extends FunctionalTest {
	
	static $fixture_file = 'CMSPageHistoryControllerTest.yml';
	
	private $versionUnpublishedCheck, $versionPublishCheck, $versionUnpublishedCheck2;
	private $page;
	
	function setUp() {
		parent::setUp();
		
		$this->loginWithPermission('ADMIN');
		
		// creates a series of published, unpublished versions of a page
		$this->page = new Page();
		$this->page->URLSegment = "test";
		$this->page->Content = "new content";
		$this->page->write(); 
		$this->versionUnpublishedCheck = $this->page->Version;
		
		$this->page->Content = "some further content";
		$this->page->write();
		$this->page->publish('Stage', 'Live');
		$this->versionPublishCheck = $this->page->Version;
		
		$this->page->Content = "No, more changes please";
		$this->page->Title = "Changing titles too";
		$this->page->write();
		$this->versionUnpublishedCheck2 = $this->page->Version;
		
		$this->page->Title = "Final Change";
		$this->page->write();
		$this->page->publish('Stage', 'Live');
		$this->versionPublishCheck2 = $this->page->Version;
	}
	
	function testGetEditForm() {

	}

	/**
	 * @todo should be less tied to cms theme
	 */
	function testVersionsForm() {
		$history = $this->get('admin/page/history/show/'. $this->page->ID);

		$form = $this->cssParser()->getBySelector("#Form_VersionsForm");
		$this->assertEquals(1, count($form));
		
		// check the page ID is present
		$hidden = $form[0]->xpath("fieldset/input[@type='hidden']");
		$this->assertFalse($hidden == null, 'Hidden ID field exists');
		$this->assertEquals(4, (int) $hidden[0]->attributes()->value);
		
		// ensure that all the versions are present in the table and displayed
		$rows = $form[0]->xpath("fieldset/table/tbody/tr");
		
		$this->assertFalse($hidden == null, "Versions exist in table");
		$this->assertEquals(4, count($rows));
		
		$expected = array(
			array('version' => $this->versionPublishCheck2, 'status' => 'published'),
			array('version' => $this->versionUnpublishedCheck2, 'status' => 'internal'),
			array('version' => $this->versionPublishCheck, 'status' => 'published'),
			array('version' => $this->versionUnpublishedCheck, 'status' => 'internal')
		);
		
		// goes the reverse order that we created in setUp();
		$i = 0;
		foreach($rows as $tr) {
			$this->assertEquals(
				sprintf('admin/page/history/show/%d/%d', $this->page->ID, $expected[$i]['version']), 
				(string) $tr->attributes()->{'data-link'}
			);
			
			$this->assertContains($expected[$i]['status'], (string) $tr->attributes()->class);
			$i++;
		}
	}

	function testDoForm() {
		
	}
	
	function testCompareForm() {
		
	}
	
	function testRevertForm() {
		
	}
	
	function testIsCompareMode() {
		
	}
}