<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\BatchActions\CMSBatchAction_Archive;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Publish;
use SilverStripe\CMS\BatchActions\CMSBatchAction_Unpublish;
use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

/**
 * Tests CMS Specific subclasses of {@see CMSBatchAction}
 */
class SiteTreeURLSegmentFieldTest extends SapphireTest
{
    /**
     * Test which pages can be published via batch actions
     */
    public function testURLSuffix()
    {
        $field = new SiteTreeURLSegmentField('URLSegment');
        $field->setURLSuffix('?foo=bar');

        $this->assertEquals('?foo=bar', $field->getURLSuffix());
        $this->assertEquals('?foo=bar', $field->getAttributes()['data-suffix']);
    }
}
