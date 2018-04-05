<?php

namespace SilverStripe\CMS\Tasks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;

/**
 * Updates legacy SiteTree link tracking into new polymorphic many_many relation.
 * This should be done for any site upgrading to 4.2.0
 */
class MigrateSiteTreeLinkingTask extends BuildTask
{
    private static $segment = 'MigrateSiteTreeLinkingTask';

    protected $title = 'Migrate SiteTree Linking Task';

    protected $description = 'Updates legacy SiteTree link tracking into new polymorphic many_many relation';

    public function run($request)
    {
        // Ensure legacy table exists
        $exists = DB::get_conn()->getSchemaManager()->hasTable('SiteTree_LinkTracking');
        if (!$exists) {
            DB::alteration_message("Table SiteTree_LinkTracking has already been migrated, or doesn't exist");
            return;
        }

        $pages = 0;

        // Ensure sync occurs on draft
        Versioned::withVersionedMode(function () use (&$pages) {
            Versioned::set_stage(Versioned::DRAFT);

            /** @var SiteTree[] $linkedPages */
            $linkedPages = SiteTree::get()
                ->innerJoin(
                    'SiteTree_LinkTracking',
                    '"SiteTree_LinkTracking"."SiteTreeID" = "SiteTree"."ID"'
                );
            foreach ($linkedPages as $page) {
                // Command page to update symlink tracking
                $page->syncLinkTracking();
                $pages++;
            }
        });
        DB::alteration_message("Migrated page links on " . SiteTree::singleton()->i18n_pluralise($pages));

        // Disable table to prevent double-migration
        DB::dont_require_table('SiteTree_LinkTracking');
    }
}
