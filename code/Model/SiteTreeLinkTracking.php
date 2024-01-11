<?php

namespace SilverStripe\CMS\Model;

use DOMElement;
use SilverStripe\Assets\Shortcodes\FileLinkTracking;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormScaffolder;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Parsers\HTMLValue;

/**
 * Adds tracking of links in any HTMLText fields which reference SiteTree or File items.
 *
 * Attaching this to any DataObject will add four fields which contain all links to SiteTree and File items
 * referenced in any HTMLText fields, and two booleans to indicate if there are any broken links. Call
 * augmentSyncLinkTracking to update those fields with any changes to those fields.
 *
 * Note that since both SiteTree and File are versioned, LinkTracking and FileTracking will
 * only be enabled for the Stage record.
 *
 * Note: To support `HasBrokenLink` for non-SiteTree classes, add a boolean `HasBrokenLink`
 * field to your `db` config and this extension will ensure it's flagged appropriately.
 *
 * @property DataObject|SiteTreeLinkTracking $owner
 * @method ManyManyThroughList<SiteTree> LinkTracking()
 *
 * @extends DataExtension<DataObject>
 */
class SiteTreeLinkTracking extends DataExtension
{
    /**
     * @var SiteTreeLinkTracking_Parser
     */
    protected $parser;

    /**
     * Inject parser for each page
     *
     * @var array
     * @config
     */
    private static $dependencies = [
        'Parser' => '%$' . SiteTreeLinkTracking_Parser::class
    ];

    private static $many_many = [
        "LinkTracking" => [
            'through' => SiteTreeLink::class,
            'from' => 'Parent',
            'to' => 'Linked',
        ],
    ];

    /**
     * Controls visibility of the Link Tracking tab
     *
     * @config
     * @see linktracking.yml
     * @var boolean
     */
    private static $show_sitetree_link_tracking = false;

    /**
     * Parser for link tracking
     *
     * @return SiteTreeLinkTracking_Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @param SiteTreeLinkTracking_Parser $parser
     * @return $this
     */
    public function setParser(SiteTreeLinkTracking_Parser $parser = null)
    {
        $this->parser = $parser;
        return $this;
    }

    public function onBeforeWrite()
    {
        // Trigger link tracking (unless this would also be triggered by FileLinkTracking)
        if (!$this->owner->hasExtension(FileLinkTracking::class)) {
            $this->owner->syncLinkTracking();
        }
    }

    /**
     * Public method to call when triggering symlink extension. Can be called externally,
     * or overridden by class implementations.
     *
     * {@see SiteTreeLinkTracking::augmentSyncLinkTracking}
     */
    public function syncLinkTracking()
    {
        $this->owner->extend('augmentSyncLinkTracking');
    }

    /**
     * Find HTMLText fields on {@link owner} to scrape for links that need tracking
     */
    public function augmentSyncLinkTracking()
    {
        // If owner is versioned, skip tracking on live
        if (Versioned::get_stage() == Versioned::LIVE && $this->owner->hasExtension(Versioned::class)) {
            return;
        }

        // Build a list of HTMLText fields, merging all linked pages together.
        $allFields = DataObject::getSchema()->fieldSpecs($this->owner);
        $linkedPages = [];
        $anyBroken = false;
        $hasTrackedFields = false;
        foreach ($allFields as $field => $fieldSpec) {
            $fieldObj = $this->owner->dbObject($field);
            if ($fieldObj instanceof DBHTMLText) {
                $hasTrackedFields = true;
                // Merge links in this field with global list.
                $linksInField = $this->trackLinksInField($field, $anyBroken);
                $linkedPages = array_merge($linkedPages, $linksInField);
            }
        }

        // We need a boolean flag instead of checking linkedPages because it can be empty when pages are removed
        if (!$hasTrackedFields) {
            return;
        }

        // Soft support for HasBrokenLink db field (e.g. SiteTree)
        if ($this->owner->hasField('HasBrokenLink')) {
            $this->owner->HasBrokenLink = $anyBroken;
        }

        // Update the "LinkTracking" many_many.
        $this->owner->LinkTracking()->setByIDList($linkedPages);
    }

    public function onAfterDelete()
    {
        // If owner is versioned, skip tracking on live
        if (Versioned::get_stage() == Versioned::LIVE && $this->owner->hasExtension(Versioned::class)) {
            return;
        }

        $this->owner->LinkTracking()->removeAll();
    }

    /**
     * Scrape the content of a field to detect anly links to local SiteTree pages or files
     *
     * @param string $fieldName The name of the field on {@link @owner} to scrape
     * @param bool &$anyBroken Will be flagged to true (by reference) if a link is broken.
     * @return int[] Array of page IDs found (associative array)
     */
    public function trackLinksInField($fieldName, &$anyBroken = false)
    {
        // Pull down current field content
        $htmlValue = HTMLValue::create($this->owner->$fieldName);

        // Process all links
        $linkedPages = [];
        $links = $this->parser->process($htmlValue);
        foreach ($links as $link) {
            // Toggle highlight class to element
            $this->toggleElementClass($link['DOMReference'], 'ss-broken', $link['Broken']);

            // Flag broken
            if ($link['Broken']) {
                $anyBroken = true;
            }

            // Collect page ids
            if ($link['Type'] === 'sitetree' && $link['Target']) {
                $pageID = (int)$link['Target'];
                $linkedPages[$pageID] = $pageID;
            }
        }

        // Update any changed content
        $this->owner->$fieldName = $htmlValue->getContent();
        return $linkedPages;
    }

    /**
     * Add the given css class to the DOM element.
     *
     * @param DOMElement $domReference Element to modify.
     * @param string $class Class name to toggle.
     * @param bool $toggle On or off.
     */
    protected function toggleElementClass(DOMElement $domReference, $class, $toggle)
    {
        // Get all existing classes.
        $classes = array_filter(explode(' ', trim($domReference->getAttribute('class') ?? '')));

        // Add or remove the broken class from the link, depending on the link status.
        if ($toggle) {
            $classes = array_unique(array_merge($classes, [$class]));
        } else {
            $classes = array_diff($classes ?? [], [$class]);
        }

        if (!empty($classes)) {
            $domReference->setAttribute('class', implode(' ', $classes));
        } else {
            $domReference->removeAttribute('class');
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        if (!$this->owner->config()->get('show_sitetree_link_tracking')) {
            $fields->removeByName('LinkTracking');
        } elseif ($this->owner->ID && !$this->owner->getField('LinkTracking')) {
            FormScaffolder::addManyManyRelationshipFields($fields, 'LinkTracking', null, true, $this->owner);
        }
    }
}
