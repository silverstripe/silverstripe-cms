<?php

namespace SilverStripe\CMS\Model;

use DOMElement;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Parsers\HTMLValue;

/**
 * Adds tracking of links in any HTMLText fields which reference SiteTree or File items.
 *
 * Attaching this to any DataObject will add four fields which contain all links to SiteTree and File items
 * referenced in any HTMLText fields, and two booleans to indicate if there are any broken links. Call
 * augmentSyncLinkTracking to update those fields with any changes to those fields.
 *
 * Note that since both SiteTree and File are versioned, LinkTracking and ImageTracking will
 * only be enabled for the Stage record.
 *
 * {@see SiteTreeTrackedPage} for the extension applied to {@see SiteTree}
 * @property DataObject|SiteTreeLinkTracking $owner
 * @property bool $HasBrokenLink True if any page or anchor is broken
 * @method ManyManyList LinkTracking() List of site pages linked on this dataobject
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

    private static $db = [
        'HasBrokenLink' => 'Boolean'
    ];

    private static $many_many = [
        "LinkTracking" => [
            'through' => SiteTreeLink::class,
            'from' => 'Parent',
            'to' => 'Linked',
        ],
    ];

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
        // Trigger link tracking
        $this->owner->syncLinkTracking();
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
        // Skip live tracking
        if (Versioned::get_stage() == Versioned::LIVE) {
            return;
        }

        // Reset boolean broken flag. This will be flagged back by trackLinksInField().
        $this->owner->HasBrokenLink = false;

        // Build a list of HTMLText fields, merging all linked pages together.
        $allFields = DataObject::getSchema()->fieldSpecs($this->owner);
        $linkedPages = [];
        foreach ($allFields as $field => $fieldSpec) {
            $fieldObj = $this->owner->dbObject($field);
            if ($fieldObj instanceof DBHTMLText) {
                // Merge links in this field with global list.
                $linksInField = $this->trackLinksInField($field);
                $linkedPages = array_merge($linkedPages, $linksInField);
            }
        }

        // Update the "LinkTracking" many_many.
        $this->owner->LinkTracking()->setByIDList($linkedPages);
    }

    /**
     * Scrape the content of a field to detect anly links to local SiteTree pages or files
     *
     * @param string $fieldName The name of the field on {@link @owner} to scrape
     * @return int[] Array of page IDs found (associative array)
     */
    public function trackLinksInField($fieldName)
    {
        // Pull down current field content
        $record = $this->owner;
        $htmlValue = HTMLValue::create($record->$fieldName);

        // Process all links
        $linkedPages = [];
        $links = $this->parser->process($htmlValue);
        foreach ($links as $link) {
            // Toggle highlight class to element
            $this->toggleElementClass($link['DOMReference'], 'ss-broken', $link['Broken']);

            // Flag broken
            if ($link['Broken']) {
                $record->HasBrokenLink = true;
            }

            // Collect page ids
            if ($link['Type'] === 'sitetree' && $link['Target']) {
                $pageID = (int)$link['Target'];
                $linkedPages[$pageID] = $pageID;
            }
        }

        // Update any changed content
        $record->$fieldName = $htmlValue->getContent();
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
        $classes = array_filter(explode(' ', trim($domReference->getAttribute('class'))));

        // Add or remove the broken class from the link, depending on the link status.
        if ($toggle) {
            $classes = array_unique(array_merge($classes, [$class]));
        } else {
            $classes = array_diff($classes, [$class]);
        }

        if (!empty($classes)) {
            $domReference->setAttribute('class', implode(' ', $classes));
        } else {
            $domReference->removeAttribute('class');
        }
    }
}
