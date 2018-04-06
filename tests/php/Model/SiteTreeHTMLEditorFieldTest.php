<?php

namespace SilverStripe\CMS\Tests\Model;

use Page;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Assets\Folder;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class SiteTreeHTMLEditorFieldTest extends FunctionalTest
{
    protected static $fixture_file = 'SiteTreeHTMLEditorFieldTest.yml';

    public function setUp()
    {
        parent::setUp();
        TestAssetStore::activate('SiteTreeHTMLEditorFieldTest');
        $this->logInWithPermission('ADMIN');

        // Write file contents
        $files = File::get()->exclude('ClassName', Folder::class);
        foreach ($files as $file) {
            $destPath = TestAssetStore::getLocalPath($file);
            Filesystem::makeFolder(dirname($destPath));
            file_put_contents($destPath, str_repeat('x', 1000000));
        }

        // Ensure all pages are published
        /** @var Page $page */
        foreach (Page::get() as $page) {
            $page->publishSingle();
        }
    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testLinkTracking()
    {
        /** @var SiteTree $sitetree */
        $sitetree = $this->objFromFixture(SiteTree::class, 'home');
        $editor   = new HTMLEditorField('Content');

        $aboutID   = $this->idFromFixture(SiteTree::class, 'about');
        $contactID = $this->idFromFixture(SiteTree::class, 'contact');

        $editor->setValue("<a href=\"[sitetree_link,id=$aboutID]\">Example Link</a>");
        $editor->saveInto($sitetree);
        $sitetree->write();
        $this->assertEquals(array($aboutID => $aboutID), $sitetree->LinkTracking()->getIdList(), 'Basic link tracking works.');

        $editor->setValue(
            "<a href=\"[sitetree_link,id=$aboutID]\"></a><a href=\"[sitetree_link,id=$contactID]\"></a>"
        );
        $editor->saveInto($sitetree);
        $sitetree->write();
        $this->assertEquals(
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

    public function testImageInsertion()
    {
        $sitetree = new SiteTree();
        $editor   = new HTMLEditorField('Content');

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

    public function testBrokenSiteTreeLinkTracking()
    {
        $sitetree = new SiteTree();
        $editor   = new HTMLEditorField('Content');

        $this->assertFalse((bool) $sitetree->HasBrokenLink);

        $editor->setValue('<p><a href="[sitetree_link,id=0]">Broken Link</a></p>');
        $editor->saveInto($sitetree);
        $sitetree->write();

        $this->assertTrue($sitetree->HasBrokenLink);

        $editor->setValue(sprintf(
            '<p><a href="[sitetree_link,id=%d]">Working Link</a></p>',
            $this->idFromFixture(SiteTree::class, 'home')
        ));
        $sitetree->HasBrokenLink = false;
        $editor->saveInto($sitetree);
        $sitetree->write();

        $this->assertFalse((bool) $sitetree->HasBrokenLink);
    }
}
