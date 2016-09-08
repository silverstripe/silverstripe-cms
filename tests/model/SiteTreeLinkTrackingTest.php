<?php

use SilverStripe\CMS\Model\SiteTreeLinkTracking_Parser;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Assets\File;
use SilverStripe\Dev\SapphireTest;


class SiteTreeLinkTrackingTest extends SapphireTest {

	protected function isBroken($content) {
		$parser = new SiteTreeLinkTracking_Parser();
		$htmlValue = Injector::inst()->create('HTMLValue', $content);
		$links = $parser->process($htmlValue);

		if (empty($links[0])) return false;
		return $links[0]['Broken'];
	}

	public function testParser() {
		$this->assertTrue($this->isBroken('<a href="[sitetree_link,id=123]">link</a>'));
		$this->assertTrue($this->isBroken('<a href="[sitetree_link,id=123]#no-such-anchor">link</a>'));
		$this->assertTrue($this->isBroken('<a href="[file_link,id=123]">link</a>'));
		$this->assertTrue($this->isBroken('<a href="">link</a>'));
		$this->assertTrue($this->isBroken('<a href="/">link</a>'));

		$this->assertFalse($this->isBroken('<a name="anchor">anchor</a>'));
		$this->assertFalse($this->isBroken('<a id="anchor">anchor</a>'));
		$this->assertTrue($this->isBroken('<a href="##anchor">anchor</a>'));

		$page = new Page();
		$page->Content = '<a name="yes-name-anchor">name</a><a id="yes-id-anchor">id</a>';
		$page->write();

		$file = new File();
		$file->write();

		$this->assertFalse($this->isBroken("<a href=\"[sitetree_link,id=$page->ID]\">link</a>"));
		$this->assertFalse($this->isBroken("<a href=\"[sitetree_link,id=$page->ID]#yes-name-anchor\">link</a>"));
		$this->assertFalse($this->isBroken("<a href=\"[sitetree_link,id=$page->ID]#yes-id-anchor\">link</a>"));
		$this->assertFalse($this->isBroken("<a href=\"[file_link,id=$file->ID]\">link</a>"));
		$this->assertTrue($this->isBroken("<a href=\"[sitetree_link,id=$page->ID]#http://invalid-anchor.com\"></a>"));
	}

	protected function highlight($content) {
		$page = new Page();
		$page->Content = $content;
		$page->write();
		return $page->Content;
	}

	public function testHighlighter() {
		$content = $this->highlight('<a href="[sitetree_link,id=123]" class="existing-class">link</a>');
		$this->assertEquals(substr_count($content, 'ss-broken'), 1, 'A ss-broken class is added to the broken link.');
		$this->assertEquals(substr_count($content, 'existing-class'), 1, 'Existing class is not removed.');

		$content = $this->highlight('<a href="[sitetree_link,id=123]">link</a>');
		$this->assertEquals(substr_count($content, 'ss-broken'), 1, 'ss-broken class is added to the broken link.');

		$otherPage = new Page();
		$otherPage->Content = '';
		$otherPage->write();

		$content = $this->highlight(
			"<a href=\"[sitetree_link,id=$otherPage->ID]\" class=\"existing-class ss-broken ss-broken\">link</a>"
		);
		$this->assertEquals(substr_count($content, 'ss-broken'), 0, 'All ss-broken classes are removed from good link');
		$this->assertEquals(substr_count($content, 'existing-class'), 1, 'Existing class is not removed.');
	}

	public function testHasBrokenFile() {
		$this->assertTrue($this->pageIsBrokenFile('[image src="someurl.jpg" id="99999999"]'));
		$this->assertFalse($this->pageIsBrokenFile('[image src="someurl.jpg"]'));
	}

	protected function pageIsBrokenFile($content) {
		$page = new Page();
		$page->Content = $content;
		$page->write();
		return $page->HasBrokenFile;
	}

}
