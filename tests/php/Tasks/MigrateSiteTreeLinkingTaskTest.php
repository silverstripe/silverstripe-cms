<?php

namespace SilverStripe\CMS\Tests\Tasks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Tasks\MigrateSiteTreeLinkingTask;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

class MigrateSiteTreeLinkingTaskTest extends SapphireTest
{
    protected static $fixture_file = 'MigrateSiteTreeLinkingTaskTest.yml';

    protected static $use_draft_site = true;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Cover db reset in case parent did not start
        if (!static::getExtraDataObjects()) {
            DataObject::reset();
            static::resetDBSchema(true, true);
        }

        // Ensure legacy SiteTree_LinkTracking table exists
        DB::get_schema()->schemaUpdate(function () {
            DB::require_table('SiteTree_LinkTracking', [
                'SiteTreeID' => 'Int',
                'ChildID' => 'Int',
                'FieldName' => 'Varchar',
            ]);
        });
    }

    protected function setUp()
    {
        parent::setUp();

        // Manually bootstrap all Content blocks with soft coded IDs (raw sql to avoid save hooks)
        $replacements = [
            '$$ABOUTID$$' => $this->idFromFixture(SiteTree::class, 'about'),
            '$$HOMEID$$' => $this->idFromFixture(SiteTree::class, 'home'),
            '$$STAFFID$$' => $this->idFromFixture(SiteTree::class, 'staff'),
        ];
        foreach (DB::query('SELECT "ID", "Content" FROM "SiteTree"') as $row) {
            $id = (int)$row['ID'];
            $content = str_replace(array_keys($replacements), array_values($replacements), $row['Content']);
            DB::prepared_query('UPDATE "SiteTree" SET "Content" = ? WHERE "ID" = ?', [$content, $id]);
        }
        DataObject::reset();
    }

    public function testLinkingMigration()
    {
        ob_start();

        DB::quiet(false);
        $task = new MigrateSiteTreeLinkingTask();
        $task->run(null);
        $this->assertContains(
            "Migrated page links on 5 Pages",
            ob_get_contents(),
            'Rewritten links are correctly reported'
        );
        DB::quiet(true);
        ob_end_clean();

        // Query links for pages
        /** @var SiteTree $home */
        $home = $this->objFromFixture(SiteTree::class, 'home');
        /** @var SiteTree $about */
        $about  = $this->objFromFixture(SiteTree::class, 'about');
        /** @var SiteTree $staff */
        $staff  = $this->objFromFixture(SiteTree::class, 'staff');
        /** @var SiteTree $action */
        $action = $this->objFromFixture(SiteTree::class, 'action');
        /** @var SiteTree $hash */
        $hash = $this->objFromFixture(SiteTree::class, 'hash_link');

        // Ensure all links are created
        $this->assertListEquals([$about->toMap(), $staff->toMap()], $home->LinkTracking());
        $this->assertListEquals([$home->toMap(), $staff->toMap()], $about->LinkTracking());
        $this->assertListEquals([$home->toMap(), $about->toMap()], $staff->LinkTracking());
        $this->assertListEquals([$home->toMap()], $action->LinkTracking());
        $this->assertListEquals([$home->toMap(), $about->toMap()], $hash->LinkTracking());
    }
}
