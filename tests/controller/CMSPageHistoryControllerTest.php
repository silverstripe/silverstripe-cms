<?php

/**
 * @package cms
 * @subpackage tests
 */

class CMSPageHistoryControllerTest extends FunctionalTest {
	
	protected static $fixture_file = 'CMSPageHistoryControllerTest.yml';
	
	private $versionUnpublishedCheck, $versionPublishCheck, $versionUnpublishedCheck2;
	private $page;
	
	public function setUp() {
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
	
	public function testGetEditForm() {
		$controller = new CMSPageHistoryController();
		
		// should get the latest version which we cannot rollback to
		$form = $controller->getEditForm($this->page->ID);
	
		$this->assertTrue($form->Actions()->dataFieldByName('action_doRollback')->isReadonly());

		$this->assertEquals($this->page->ID, $form->Fields()->dataFieldByName('ID')->Value());
		$this->assertEquals($this->versionPublishCheck2, $form->Fields()->dataFieldByName('Version')->Value());

		$this->assertContains(
			'Currently viewing the latest version',
			$form->Fields()->fieldByName('Root.Main.CurrentlyViewingMessage')->getContent()
		);
		
		// edit form with a given version
		$form = $controller->getEditForm($this->page->ID, null, $this->versionPublishCheck);
		$this->assertFalse($form->Actions()->dataFieldByName('action_doRollback')->isReadonly());

		$this->assertEquals($this->page->ID, $form->Fields()->dataFieldByName('ID')->Value());
		$this->assertEquals($this->versionPublishCheck, $form->Fields()->dataFieldByName('Version')->Value());
		$this->assertContains(
			sprintf("Currently viewing version %s.", $this->versionPublishCheck),
			$form->Fields()->fieldByName('Root.Main.CurrentlyViewingMessage')->getContent()
		);
		
		// check that compare mode updates the message
		$form = $controller->getEditForm($this->page->ID, null, $this->versionPublishCheck, $this->versionPublishCheck2);
		$this->assertContains(
			sprintf("Comparing versions %s", $this->versionPublishCheck),
			$form->Fields()->fieldByName('Root.Main.CurrentlyViewingMessage')->getContent()
		);
		
		$this->assertContains(
			sprintf("and %s", $this->versionPublishCheck2),
			$form->Fields()->fieldByName('Root.Main.CurrentlyViewingMessage')->getContent()
		);
	}

	/**
	 * @todo should be less tied to cms theme.
	 * @todo check highlighting for comparing pages.
	 */
	public function testVersionsForm() {
		$history = $this->get('admin/pages/history/show/'. $this->page->ID);
		$form = $this->cssParser()->getBySelector('#Form_VersionsForm');
		
		$this->assertEquals(1, count($form));
		
		// check the page ID is present
		$hidden = $form[0]->xpath("fieldset/input[@type='hidden']");
		
		$this->assertThat($hidden, $this->logicalNot($this->isNull()), 'Hidden ID field exists');
		$this->assertEquals($this->page->ID, (int) $hidden[0]->attributes()->value);
		
		// ensure that all the versions are present in the table and displayed
		$rows = $form[0]->xpath("fieldset/table/tbody/tr");
		$this->assertEquals(4, count($rows));
	}
	
	public function testVersionsFormTableContainsInformation() {
		$history = $this->get('admin/pages/history/show/'. $this->page->ID);
		$form = $this->cssParser()->getBySelector('#Form_VersionsForm');
		$rows = $form[0]->xpath("fieldset/table/tbody/tr");
		
		$expected = array(
			array('version' => $this->versionPublishCheck2, 'status' => 'published'),
			array('version' => $this->versionUnpublishedCheck2, 'status' => 'internal'),
			array('version' => $this->versionPublishCheck, 'status' => 'published'),
			array('version' => $this->versionUnpublishedCheck, 'status' => 'internal')
		);
		
		// goes the reverse order that we created in setUp()
		$i = 0;
		foreach($rows as $tr) {
			// data-link must be present for the javascript to load new
			$this->assertContains($expected[$i]['status'], (string) $tr->attributes()->class);
			$i++;
		}
		
		// test highlighting
		$this->assertContains('active', (string) $rows[0]->attributes()->class);
		$this->assertThat((string) $rows[1]->attributes()->class, $this->logicalNot($this->stringContains('active')));
	}
	
	public function testVersionsFormSelectsUnpublishedCheckbox() {
		$history = $this->get('admin/pages/history/show/'. $this->page->ID);
		$checkbox = $this->cssParser()->getBySelector('#Form_VersionsForm_ShowUnpublished');

		$this->assertThat($checkbox[0], $this->logicalNot($this->isNull()));
		$checked = $checkbox[0]->attributes()->checked;

		$this->assertThat($checked, $this->logicalNot($this->stringContains('checked')));
		
		// viewing an unpublished
		$history = $this->get('admin/pages/history/show/'.$this->page->ID .'/'.$this->versionUnpublishedCheck);
		$checkbox = $this->cssParser()->getBySelector('#Form_VersionsForm_ShowUnpublished');

		$this->assertThat($checkbox[0], $this->logicalNot($this->isNull()));
		$this->assertEquals('checked', (string) $checkbox[0]->attributes()->checked);
	}
}
