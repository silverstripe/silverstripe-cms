<?php

namespace SilverStripe\CMS\Model;

use DOMElement;
use SilverStripe\Assets\File;
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
 * {@see SiteTreeFileExtension} for the extension applied to {@see File}
 *
 * @property SiteTree $owner
 *
 * @property bool $HasBrokenFile
 * @property bool $HasBrokenLink
 *
 * @method ManyManyList LinkTracking() List of site pages linked on this page.
 * @method ManyManyList ImageTracking() List of Images linked on this page.
 * @method ManyManyList BackLinkTracking List of site pages that link to this page.
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

    private static $db = array(
        "HasBrokenFile" => "Boolean",
        "HasBrokenLink" => "Boolean"
    );

    private static $many_many = array(
        "LinkTracking" => SiteTree::class,
        "ImageTracking" => File::class,  // {@see SiteTreeFileExtension}
    );

    private static $belongs_many_many = array(
        "BackLinkTracking" => SiteTree::class . '.LinkTracking',
    );

    /**
     * Tracked images are considered owned by this page
     *
     * @config
     * @var array
     */
    private static $owns = array(
        "ImageTracking"
    );

    private static $many_many_extraFields = array(
        "LinkTracking" => array("FieldName" => "Varchar"),
        "ImageTracking" => array("FieldName" => "Varchar")
    );

    /**
     * Scrape the content of a field to detect anly links to local SiteTree pages or files
     *
     * @param string $fieldName The name of the field on {@link @owner} to scrape
     */
    public function trackLinksInField($fieldName)
    {
        $record = $this->owner;

        $linkedPages = array();
        $linkedFiles = array();

        $htmlValue = HTMLValue::create($record->$fieldName);
        $links = $this->parser->process($htmlValue);

        // Highlight broken links in the content.
        foreach ($links as $link) {
            // Skip links without domelements
            if (!isset($link['DOMReference'])) {
                continue;
            }

            /** @var DOMElement $domReference */
            $domReference = $link['DOMReference'];
            $classStr = trim($domReference->getAttribute('class'));
            if (!$classStr) {
                $classes = array();
            } else {
                $classes = explode(' ', $classStr);
            }

            // Add or remove the broken class from the link, depending on the link status.
            if ($link['Broken']) {
                $classes = array_unique(array_merge($classes, array('ss-broken')));
            } else {
                $classes = array_diff($classes, array('ss-broken'));
            }

            if (!empty($classes)) {
                $domReference->setAttribute('class', implode(' ', $classes));
            } else {
                $domReference->removeAttribute('class');
            }
        }
        $record->$fieldName = $htmlValue->getContent();

        // Populate link tracking for internal links & links to asset files.
        foreach ($links as $link) {
            switch ($link['Type']) {
                case 'sitetree':
                    if ($link['Broken']) {
                        $record->HasBrokenLink = true;
                    } else {
                        $linkedPages[] = $link['Target'];
                    }
                    break;

                case 'file':
                case 'image':
                    if ($link['Broken']) {
                        $record->HasBrokenFile = true;
                    } else {
                        $linkedFiles[] = $link['Target'];
                    }
                    break;

                default:
                    if ($link['Broken']) {
                        $record->HasBrokenLink = true;
                    }
                    break;
            }
        }

        // Update the "LinkTracking" many_many
        if ($record->ID
            && $record->getSchema()->manyManyComponent(get_class($record), 'LinkTracking')
            && ($tracker = $record->LinkTracking())
        ) {
            $tracker->removeByFilter(array(
                sprintf('"FieldName" = ? AND "%s" = ?', $tracker->getForeignKey())
                    => array($fieldName, $record->ID)
            ));

            if ($linkedPages) {
                foreach ($linkedPages as $item) {
                    $tracker->add($item, array('FieldName' => $fieldName));
                }
            }
        }

        // Update the "ImageTracking" many_many
        if ($record->ID
            && $record->getSchema()->manyManyComponent(get_class($record), 'ImageTracking')
            && ($tracker = $record->ImageTracking())
        ) {
            $tracker->removeByFilter(array(
                sprintf('"FieldName" = ? AND "%s" = ?', $tracker->getForeignKey())
                    => array($fieldName, $record->ID)
            ));

            if ($linkedFiles) {
                foreach ($linkedFiles as $item) {
                    $tracker->add($item, array('FieldName' => $fieldName));
                }
            }
        }
    }

    /**
     * Find HTMLText fields on {@link owner} to scrape for links that need tracking
     *
     * @todo Support versioned many_many for per-stage page link tracking
     */
    public function augmentSyncLinkTracking()
    {
        // Skip live tracking
        if (Versioned::get_stage() == Versioned::LIVE) {
            return;
        }

        // Reset boolean broken flags
        $this->owner->HasBrokenLink = false;
        $this->owner->HasBrokenFile = false;

        // Build a list of HTMLText fields
        $allFields = DataObject::getSchema()->fieldSpecs($this->owner);
        $htmlFields = array();
        foreach ($allFields as $field => $fieldSpec) {
            $fieldObj = $this->owner->dbObject($field);
            if ($fieldObj instanceof DBHTMLText) {
                $htmlFields[] = $field;
            }
        }

        foreach ($htmlFields as $field) {
            $this->trackLinksInField($field);
        }
    }
}
