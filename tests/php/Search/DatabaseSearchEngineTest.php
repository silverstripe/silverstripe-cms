<?php

namespace SilverStripe\CMS\Tests\Search;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DB;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\Search\FulltextSearchable;

class DatabaseSearchEngineTest extends SapphireTest
{
    protected $usesDatabase = true;

    /**
     * @var bool InnoDB doesn't update indexes until transactions are committed
     */
    protected $usesTransactions = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Postgres doesn't refresh TSearch indexes when the schema changes after CREATE TABLE
        // MySQL will need a different table type
        if (static::$tempDB) {
            static::$tempDB->kill();
            Config::modify();
        }
        FulltextSearchable::enable();
        static::$tempDB->build();
        static::resetDBSchema(true);
    }

    /**
     * Validate that https://github.com/silverstripe/silverstripe-cms/issues/3212 is fixed
     */
    public function testSearchEngineEscapeAs()
    {
        $page = new SiteTree();
        $page->Title = "This page provides food as bar";
        $page->write();
        $page->publishRecursive();

        $results = DB::get_conn()->searchEngine([ SiteTree::class, File::class ], "foo* as* bar*", 0, 100, "\"Relevance\" DESC", "", true);

        $this->assertCount(1, $results);
        $this->assertEquals(
            "This page provides food as bar",
            $results->First()->Title
        );
    }
}
