<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\Assets\File;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\SSViewer;

/**
 * Extension applied to {@see File} object to track links to {@see SiteTree} records.
 *
 * {@see SiteTreeLinkTracking} for the extension applied to {@see SiteTree}
 *
 * Note that since both SiteTree and File are versioned, LinkTracking and ImageTracking will
 * only be enabled for the Stage record.
 *
 * @property File $owner
 */
class SiteTreeFileExtension extends DataExtension
{
    private static $belongs_many_many = array(
        'BackLinkTracking' => SiteTree::class . '.ImageTracking' // {@see SiteTreeLinkTracking}
    );

    /**
     * Images tracked by pages are owned by those pages
     *
     * @config
     * @var array
     */
    private static $owned_by = array(
        'BackLinkTracking'
    );

    private static $casting = array(
        'BackLinkHTMLList' => 'HTMLFragment'
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->insertAfter(
            'LastEdited',
            ReadonlyField::create(
                'BackLinkCount',
                _t(__CLASS__.'.BACKLINKCOUNT', 'Used on:'),
                $this->BackLinkTracking()->count() . ' ' . _t(__CLASS__.'.PAGES', 'page(s)')
            )
                ->addExtraClass('cms-description-toggle')
                ->setDescription($this->BackLinkHTMLList())
        );
    }

    /**
     * Generate an HTML list which provides links to where a file is used.
     *
     * @return string
     */
    public function BackLinkHTMLList()
    {
        $viewer = SSViewer::create(["type" => "Includes", self::class . "_description"]);

        return $viewer->process($this->owner);
    }

    /**
     * Extend through {@link updateBackLinkTracking()} in your own {@link Extension}.
     *
     * @return ManyManyList
     */
    public function BackLinkTracking()
    {
        // @todo remove coupling with Subsites
        if (class_exists(Subsite::class)) {
            $rememberSubsiteFilter = Subsite::$disable_subsite_filter;
            Subsite::disable_subsite_filter(true);
        }

        $links = $this->owner->getManyManyComponents('BackLinkTracking');
        $this->owner->extend('updateBackLinkTracking', $links);

        if (class_exists(Subsite::class)) {
            Subsite::disable_subsite_filter($rememberSubsiteFilter);
        }

        return $links;
    }

    /**
     * @todo Unnecessary shortcut for AssetTableField, coupled with cms module.
     *
     * @return int
     */
    public function BackLinkTrackingCount()
    {
        $pages = $this->owner->BackLinkTracking();
        if ($pages) {
            return $pages->count();
        } else {
            return 0;
        }
    }

    /**
     * Updates link tracking in the current stage.
     */
    public function onAfterDelete()
    {
        // Skip live stage
        if (Versioned::get_stage() === Versioned::LIVE) {
            return;
        }

        // We query the explicit ID list, because BackLinkTracking will get modified after the stage
        // site does its thing
        $brokenPageIDs = $this->owner->BackLinkTracking()->column("ID");
        if ($brokenPageIDs) {
            // This will syncLinkTracking on the same stage as this file
            $brokenPages = SiteTree::get()->byIDs($brokenPageIDs);
            foreach ($brokenPages as $brokenPage) {
                $brokenPage->write();
            }
        }
    }
}
