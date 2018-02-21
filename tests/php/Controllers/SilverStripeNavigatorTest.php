<?php

namespace SilverStripe\CMS\Tests\Controllers;

use SilverStripe\CMS\Controllers\SilverStripeNavigator;
use SilverStripe\CMS\Controllers\SilverStripeNavigatorItem_ArchiveLink;
use SilverStripe\CMS\Controllers\SilverStripeNavigatorItem_LiveLink;
use SilverStripe\CMS\Controllers\SilverStripeNavigatorItem_StageLink;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

class SilverStripeNavigatorTest extends SapphireTest
{
    protected static $fixture_file = 'CMSMainTest.yml';

    protected static $extra_dataobjects = [
        SilverStripeNavigatorTest\UnstagedRecord::class,
    ];

    public function testGetItems()
    {
        $page = $this->objFromFixture('Page', 'page1');
        $navigator = new SilverStripeNavigator($page);

        $items = $navigator->getItems();
        $classes = array_map('get_class', $items->toArray());
        $this->assertContains(
            SilverStripeNavigatorItem_StageLink::class,
            $classes,
            'Adds default classes'
        );

        $this->assertContains(
            SilverStripeNavigatorTest_TestItem::class,
            $classes,
            'Autodiscovers new classes'
        );

        // Non-versioned items don't have stage / live
    }

    public function testCanView()
    {
        $page = $this->objFromFixture('Page', 'page1');
        $admin = $this->objFromFixture(Member::class, 'admin');
        $navigator = new SilverStripeNavigator($page);

        // TODO Shouldn't be necessary but SapphireTest logs in as ADMIN by default
        $this->logInWithPermission('CMS_ACCESS_CMSMain');
        $items = $navigator->getItems();
        $classes = array_map('get_class', $items->toArray());
        $this->assertNotContains(SilverStripeNavigatorTest_ProtectedTestItem::class, $classes);

        $this->logInWithPermission('ADMIN');
        $items = $navigator->getItems();
        $classes = array_map('get_class', $items->toArray());
        $this->assertContains(SilverStripeNavigatorTest_ProtectedTestItem::class, $classes);

        // Unversioned record shouldn't be viewable in stage / live specific views
        $unversioned = new SilverStripeNavigatorTest\UnstagedRecord();
        $navigator2 = new SilverStripeNavigator($unversioned);
        $classes = array_map('get_class', $navigator2->getItems()->toArray());
        $this->assertNotContains(SilverStripeNavigatorItem_LiveLink::class, $classes);
        $this->assertNotContains(SilverStripeNavigatorItem_StageLink::class, $classes);
        $this->assertNotContains(SilverStripeNavigatorItem_ArchiveLink::class, $classes);
    }
}
