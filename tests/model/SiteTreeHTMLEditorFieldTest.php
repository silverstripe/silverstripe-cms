<?php
class SiteTreeHtmlEditorFieldTest extends FunctionalTest {
	protected static $fixture_file = 'SiteTreeHtmlEditorFieldTest.yml';

	protected static $use_draft_site = true;

	public function testLinkTracking() {
		$sitetree = $this->objFromFixture('SiteTree', 'home');
		$editor   = new HtmlEditorField('Content');

		$aboutID   = $this->idFromFixture('SiteTree', 'about');
		$contactID = $this->idFromFixture('SiteTree', 'contact');

		$editor->setValue("<a href=\"[sitetree_link,id=$aboutID]\">Example Link</a>");
		$editor->saveInto($sitetree);
		$sitetree->write();
		$this->assertEquals(array($aboutID => $aboutID), $sitetree->LinkTracking()->getIdList(), 'Basic link tracking works.');

		$editor->setValue (
			"<a href=\"[sitetree_link,id=$aboutID]\"></a><a href=\"[sitetree_link,id=$contactID]\"></a>"
		);
		$editor->saveInto($sitetree);
		$sitetree->write();
		$this->assertEquals (
			array($aboutID => $aboutID, $contactID => $contactID),
			$sitetree->LinkTracking()->getIdList(),
			'Tracking works on multiple links'
		);

		$editor->setValue(null);
		$editor->saveInto($sitetree);
		$sitetree->write();
		$this->assertEquals(array(), $sitetree->LinkTracking()->getIdList(), 'Link tracking is removed when links are.');

		// Legacy support - old CMS versions added link shortcodes with spaces instead of commas
		$editor->setValue("<a href=\"[sitetree_link id=$aboutID]\">Example Link</a>");
		$editor->saveInto($sitetree);
		$sitetree->write();
		$this->assertEquals(
			array($aboutID => $aboutID),
			$sitetree->LinkTracking()->getIdList(),
			'Link tracking with space instead of comma in shortcode works.'
		);
	}

	public function testFileLinkTracking() {
		$sitetree = $this->objFromFixture('SiteTree', 'home');
		$editor   = new HtmlEditorField('Content');
		$fileID   = $this->idFromFixture('File', 'example_file');

		$editor->setValue(sprintf(
			'<p><a href="[file_link,id=%d]">Example File</a></p>',
			$fileID
		));
		$editor->saveInto($sitetree);
		$sitetree->write();
		$this->assertEquals (
			array($fileID => $fileID), $sitetree->ImageTracking()->getIDList(), 'Links to assets are tracked.'
		);

		$editor->setValue(null);
		$editor->saveInto($sitetree);
		$sitetree->write();
		$this->assertEquals(array(), $sitetree->ImageTracking()->getIdList(), 'Asset tracking is removed with links.');

		// Legacy support - old CMS versions added link shortcodes with spaces instead of commas
		$editor->setValue(sprintf(
			'<p><a href="[file_link id=%d]">Example File</a></p>',
			$fileID
		));
		$editor->saveInto($sitetree);
		$sitetree->write();
		$this->assertEquals(
			array($fileID => $fileID),
			$sitetree->ImageTracking()->getIDList(),
			'Link tracking with space instead of comma in shortcode works.'
		);
	}

	public function testImageInsertion() {
		$sitetree = new SiteTree();
		$editor   = new HtmlEditorField('Content');

		$editor->setValue('<img src="assets/example.jpg" />');
		$editor->saveInto($sitetree);
		$sitetree->write();

		$parser = new CSSContentParser($sitetree->Content);
		$xml = $parser->getByXpath('//img');
		$this->assertEquals('', (string)$xml[0]['alt'], 'Alt tags are added by default.');
		$this->assertEquals('', (string)$xml[0]['title'], 'Title tags are added by default.');

		$editor->setValue('<img src="assets/example.jpg" alt="foo" title="bar" />');
		$editor->saveInto($sitetree);
		$sitetree->write();

		$parser = new CSSContentParser($sitetree->Content);
		$xml = $parser->getByXpath('//img');
		$this->assertEquals('foo', (string)$xml[0]['alt'], 'Alt tags are preserved.');
		$this->assertEquals('bar', (string)$xml[0]['title'], 'Title tags are preserved.');
	}

	public function testImageTracking() {
		$sitetree = $this->objFromFixture('SiteTree', 'home');
		$editor   = new HtmlEditorField('Content');
		$fileID   = $this->idFromFixture('Image', 'example_image');

		$editor->setValue('<img src="assets/example.jpg" />');
		$editor->saveInto($sitetree);
		$sitetree->write();
		$this->assertEquals (
			array($fileID => $fileID), $sitetree->ImageTracking()->getIDList(), 'Inserted images are tracked.'
		);

		$editor->setValue(null);
		$editor->saveInto($sitetree);
		$sitetree->write();
		$this->assertEquals (
			array(), $sitetree->ImageTracking()->getIDList(), 'Tracked images are deleted when removed.'
		);
	}

	public function testBrokenSiteTreeLinkTracking() {
		$sitetree = new SiteTree();
		$editor   = new HtmlEditorField('Content');

		$this->assertFalse((bool) $sitetree->HasBrokenLink);

		$editor->setValue('<p><a href="[sitetree_link,id=0]">Broken Link</a></p>');
		$editor->saveInto($sitetree);
		$sitetree->write();

		$this->assertTrue($sitetree->HasBrokenLink);

		$editor->setValue(sprintf (
			'<p><a href="[sitetree_link,id=%d]">Working Link</a></p>',
			$this->idFromFixture('SiteTree', 'home')
		));
		$sitetree->HasBrokenLink = false;
		$editor->saveInto($sitetree);
		$sitetree->write();

		$this->assertFalse((bool) $sitetree->HasBrokenLink);
	}

	public function testBrokenFileLinkTracking() {
		$sitetree = new SiteTree();
		$editor   = new HtmlEditorField('Content');

		$this->assertFalse((bool) $sitetree->HasBrokenFile);

		$editor->setValue('<p><a href="[file_link,id=0]">Broken Link</a></p>');
		$editor->saveInto($sitetree);
		$sitetree->write();

		$this->assertTrue($sitetree->HasBrokenFile);

		$editor->setValue(sprintf (
			'<p><a href="[file_link,id=%d]">Working Link</a></p>',
			$this->idFromFixture('File', 'example_file')
		));
		$sitetree->HasBrokenFile = false;
		$editor->saveInto($sitetree);
		$sitetree->write();

		$this->assertFalse((bool) $sitetree->HasBrokenFile);
	}

}
