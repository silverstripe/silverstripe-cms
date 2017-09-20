<?php

namespace SilverStripe\CMS\Tasks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * Rewrites plain internal HTML links into shortcode form, using existing link tracking information.
 */
class MigrateSiteTreeLinkingTask extends BuildTask
{

    private static $segment = 'MigrateSiteTreeLinkingTask';

    protected $title = 'Migrate SiteTree Linking Task';

    protected $description = 'Rewrites plain internal HTML links into shortcode form, using existing link tracking information.';

    public function run($request)
    {
        $pages = 0;
        $links = 0;

        $linkedPages = new DataList(SiteTree::class);
        $linkedPages = $linkedPages->innerJoin('SiteTree_LinkTracking', '"SiteTree_LinkTracking"."SiteTreeID" = "SiteTree"."ID"');
        if ($linkedPages) {
            foreach ($linkedPages as $page) {
                $tracking = DB::prepared_query(
                    'SELECT "ChildID", "FieldName" FROM "SiteTree_LinkTracking" WHERE "SiteTreeID" = ?',
                    array($page->ID)
                )->map();

                foreach ($tracking as $childID => $fieldName) {
                    $linked = DataObject::get_by_id(SiteTree::class, $childID);

                    // TOOD: Replace in all HTMLText fields
                    $page->Content = preg_replace(
                        "/href *= *([\"']?){$linked->URLSegment}\/?/i",
                        "href=$1[sitetree_link,id={$linked->ID}]",
                        $page->Content,
                        -1,
                        $replaced
                    );

                    if ($replaced) {
                        $links += $replaced;
                    }
                }

                $page->write();
                $pages++;
            }
        }

        echo "Rewrote $links link(s) on $pages page(s) to use shortcodes.\n";
    }
}
