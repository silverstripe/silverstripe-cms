<?php

namespace SilverStripe\CMS\Model;

use Page;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyTransformation;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\HTML;

/**
 * Virtual Page creates an instance of a  page, with the same fields that the original page had, but readonly.
 * This allows you can have a page in mulitple places in the site structure, with different children without
 * duplicating the content.
 *
 * Note: This Only duplicates $db fields and not the $has_one etc..
 *
 * @method SiteTree CopyContentFrom()
 * @property int $CopyContentFromID
 */
class VirtualPage extends Page
{
    private static $description = 'Displays the content of another page';

    public static $virtualFields;

    /**
     * @var array Define fields that are not virtual - the virtual page must define these fields themselves.
     * Note that anything in {@link self::config()->initially_copied_fields} is implicitly included in this list.
     */
    private static $non_virtual_fields = array(
        "ID",
        "ClassName",
        "ObsoleteClassName",
        "SecurityTypeID",
        "OwnerID",
        "ParentID",
        "URLSegment",
        "Sort",
        "Status",
        'ShowInMenus',
        // 'Locale'
        'ShowInSearch',
        'Version',
        "Embargo",
        "Expiry",
        "CanViewType",
        "CanEditType",
        "CopyContentFromID",
        "HasBrokenLink",
    );

    /**
     * @var array Define fields that are initially copied to virtual pages but left modifiable after that.
     */
    private static $initially_copied_fields = array(
        'ShowInMenus',
        'ShowInSearch',
        'URLSegment',
    );

    private static $has_one = array(
        "CopyContentFrom" => SiteTree::class,
    );

    private static $owns = array(
        "CopyContentFrom",
    );

    private static $db = array(
        "VersionID" => "Int",
    );

    private static $table_name = 'VirtualPage';

    /**
     * Generates the array of fields required for the page type.
     *
     * @return array
     */
    public function getVirtualFields()
    {
        // Check if copied page exists
        $record = $this->CopyContentFrom();
        if (!$record || !$record->exists()) {
            return array();
        }

        // Diff db with non-virtual fields
        $fields = array_keys(static::getSchema()->fieldSpecs($record));
        $nonVirtualFields = $this->getNonVirtualisedFields();
        return array_diff($fields, $nonVirtualFields);
    }

    /**
     * List of fields or properties to never virtualise
     *
     * @return array
     */
    public function getNonVirtualisedFields()
    {
        $config = self::config();
        return array_merge(
            $config->get('non_virtual_fields'),
            $config->get('initially_copied_fields')
        );
    }

    public function setCopyContentFromID($val)
    {
        // Sanity check to prevent pages virtualising other virtual pages
        if ($val && DataObject::get_by_id(SiteTree::class, $val) instanceof VirtualPage) {
            $val = 0;
        }
        return $this->setField("CopyContentFromID", $val);
    }

    public function ContentSource()
    {
        $copied = $this->CopyContentFrom();
        if ($copied && $copied->exists()) {
            return $copied;
        }
        return $this;
    }

    /**
     * For VirtualPage, add a canonical link tag linking to the original page
     * See TRAC #6828 & http://support.google.com/webmasters/bin/answer.py?hl=en&answer=139394
     *
     * @param boolean $includeTitle Show default <title>-tag, set to false for custom templating
     * @return string The XHTML metatags
     */
    public function MetaTags($includeTitle = true)
    {
        $tags = parent::MetaTags($includeTitle);
        $copied = $this->CopyContentFrom();
        if ($copied && $copied->exists()) {
            $tags .= HTML::createTag('link', [
                'rel' => 'canonical',
                'href' => $copied->Link()
            ]);
            $tags .= "\n";
        }
        return $tags;
    }

    public function allowedChildren()
    {
        $copy = $this->CopyContentFrom();
        if ($copy && $copy->exists()) {
            return $copy->allowedChildren();
        }
        return array();
    }

    public function syncLinkTracking()
    {
        if ($this->CopyContentFromID) {
            $this->HasBrokenLink = Versioned::get_by_stage(SiteTree::class, Versioned::DRAFT)
                ->filter('ID', $this->CopyContentFromID)
                ->count() === 0;
        } else {
            $this->HasBrokenLink = true;
        }
    }

    /**
     * We can only publish the page if there is a published source page
     *
     * @param Member $member Member to check
     * @return bool
     */
    public function canPublish($member = null)
    {
        return $this->isPublishable() && parent::canPublish($member);
    }

    /**
     * Returns true if is page is publishable by anyone at all
     * Return false if the source page isn't published yet.
     *
     * Note that isPublishable doesn't affect ete from live, only publish.
     */
    public function isPublishable()
    {
        // No source
        if (!$this->CopyContentFrom() || !$this->CopyContentFrom()->ID) {
            return false;
        }

        // Unpublished source
        if (!Versioned::get_versionnumber_by_stage(
            SiteTree::class,
            'Live',
            $this->CopyContentFromID
        )) {
            return false;
        }

        // Default - publishable
        return true;
    }

    /**
     * Generate the CMS fields from the fields from the original page.
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            // Setup the linking to the original page.
            $copyContentFromField = TreeDropdownField::create(
                'CopyContentFromID',
                _t(self::class . '.CHOOSE', "Linked Page"),
                SiteTree::class
            );

            // Setup virtual fields
            if ($virtualFields = $this->getVirtualFields()) {
                $roTransformation = new ReadonlyTransformation();
                foreach ($virtualFields as $virtualField) {
                    if ($fields->dataFieldByName($virtualField)) {
                        $fields->replaceField(
                            $virtualField,
                            $fields->dataFieldByName($virtualField)->transform($roTransformation)
                        );
                    }
                }
            }

            $msgs = array();

            $fields->addFieldToTab('Root.Main', $copyContentFromField, 'Title');

            // Create links back to the original object in the CMS
            if ($this->CopyContentFrom()->exists()) {
                $link = HTML::createTag(
                    'a',
                    [
                        'class' => 'cmsEditlink',
                        'href' => 'admin/pages/edit/show/' . $this->CopyContentFromID,
                    ],
                    _t(self::class . '.EditLink', 'edit')
                );
                $msgs[] = _t(
                    self::class . '.HEADERWITHLINK',
                    "This is a virtual page copying content from \"{title}\" ({link})",
                    [
                        'title' => $this->CopyContentFrom()->obj('Title'),
                        'link'  => $link,
                    ]
                );
            } else {
                $msgs[] = _t(self::class . '.HEADER', "This is a virtual page");
                $msgs[] = _t(
                    'SilverStripe\\CMS\\Model\\SiteTree.VIRTUALPAGEWARNING',
                    'Please choose a linked page and save first in order to publish this page'
                );
            }
            if ($this->CopyContentFromID && !Versioned::get_versionnumber_by_stage(
                SiteTree::class,
                Versioned::LIVE,
                $this->CopyContentFromID
            )) {
                $msgs[] = _t(
                    'SilverStripe\\CMS\\Model\\SiteTree.VIRTUALPAGEDRAFTWARNING',
                    'Please publish the linked page in order to publish the virtual page'
                );
            }

            $fields->addFieldToTab("Root.Main", new LiteralField(
                'VirtualPageMessage',
                '<div class="message notice">' . implode('. ', $msgs) . '.</div>'
            ), 'CopyContentFromID');
        });

        return parent::getCMSFields();
    }

    public function onBeforeWrite()
    {
        $this->refreshFromCopied();
        parent::onBeforeWrite();
    }

    /**
     * Copy any fields from the copied record to bootstrap /backup
     */
    protected function refreshFromCopied()
    {
        // Skip if copied record isn't available
        $source = $this->CopyContentFrom();
        if (!$source || !$source->exists()) {
            return;
        }

        // We also want to copy certain, but only if we're copying the source page for the first
        // time. After this point, the user is free to customise these for the virtual page themselves.
        if ($this->isChanged('CopyContentFromID', 2) && $this->CopyContentFromID) {
            foreach (self::config()->get('initially_copied_fields') as $fieldName) {
                $this->$fieldName = $source->$fieldName;
            }
        }

        // Copy fields to the original record in case the class type changes
        foreach ($this->getVirtualFields() as $virtualField) {
            $this->$virtualField = $source->$virtualField;
        }
    }

    public function getSettingsFields()
    {
        $fields = parent::getSettingsFields();
        if (!$this->CopyContentFrom()->exists()) {
            $fields->addFieldToTab(
                "Root.Settings",
                new LiteralField(
                    'VirtualPageWarning',
                    '<div class="message notice">'
                    . _t(
                        'SilverStripe\\CMS\\Model\\SiteTree.VIRTUALPAGEWARNINGSETTINGS',
                        'Please choose a linked page in the main content fields in order to publish'
                    )
                    . '</div>'
                ),
                'ClassName'
            );
        }

        return $fields;
    }

    public function validate()
    {
        $result = parent::validate();

        // "Can be root" validation
        $orig = $this->CopyContentFrom();
        if ($orig && $orig->exists() && !$orig->config()->get('can_be_root') && !$this->ParentID) {
            $result->addError(
                _t(
                    self::class . '.PageTypNotAllowedOnRoot',
                    'Original page type "{type}" is not allowed on the root level for this virtual page',
                    array('type' => $orig->i18n_singular_name())
                ),
                ValidationResult::TYPE_ERROR,
                'CAN_BE_ROOT_VIRTUAL'
            );
        }

        return $result;
    }

    /**
     * @deprecated 4.2..5.0
     */
    public function updateImageTracking()
    {
        Deprecation::notice('5.0', 'This will be removed in 5.0');

        // Doesn't work on unsaved records
        if (!$this->isInDB()) {
            return;
        }

        // Remove CopyContentFrom() from the cache
        unset($this->components['CopyContentFrom']);

        // Update ImageTracking
        $copyContentFrom = $this->CopyContentFrom();
        if (!$copyContentFrom || !$copyContentFrom->isInDB()) {
            return;
        }

        $this->FileTracking()->setByIDList($copyContentFrom->FileTracking()->column('ID'));
    }

    public function CMSTreeClasses()
    {
        $parentClass = sprintf(
            ' VirtualPage-%s',
            Convert::raw2htmlid($this->CopyContentFrom()->ClassName)
        );
        return parent::CMSTreeClasses() . $parentClass;
    }

    /**
     * Use the target page's class name for fetching templates - as we need to take on its appearance
     *
     * @param string $suffix
     * @return array
     */
    public function getViewerTemplates($suffix = '')
    {
        $copy = $this->CopyContentFrom();
        if ($copy && $copy->exists()) {
            return $copy->getViewerTemplates($suffix);
        }

        return parent::getViewerTemplates($suffix);
    }

    /**
     * Allow attributes on the master page to pass
     * through to the virtual page
     *
     * @param string $field
     * @return mixed
     */
    public function __get($field)
    {
        if (parent::hasMethod($funcName = "get$field")) {
            return $this->$funcName();
        }
        if (parent::hasField($field) || ($field === 'ID' && !$this->exists())) {
            return $this->getField($field);
        }
        if (($copy = $this->CopyContentFrom()) && $copy->exists()) {
            return $copy->$field;
        }
        return null;
    }

    public function getField($field)
    {
        if ($this->isFieldVirtualised($field)) {
            return $this->CopyContentFrom()->getField($field);
        }
        return parent::getField($field);
    }

    /**
     * Check if given field is virtualised
     *
     * @param string $field
     * @return bool
     */
    public function isFieldVirtualised($field)
    {
        // Don't defer if field is non-virtualised
        $ignore = $this->getNonVirtualisedFields();
        if (in_array($field, $ignore)) {
            return false;
        }

        // Don't defer if no virtual page
        $copied = $this->CopyContentFrom();
        if (!$copied || !$copied->exists()) {
            return false;
        }

        // Check if copied object has this field
        return $copied->hasField($field);
    }

    /**
     * Pass unrecognized method calls on to the original data object
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (parent::hasMethod($method)) {
            return parent::__call($method, $args);
        } else {
            return call_user_func_array(array($this->CopyContentFrom(), $method), $args);
        }
    }

    /**
     * @param string $field
     * @return bool
     */
    public function hasField($field)
    {
        if (parent::hasField($field)) {
            return true;
        }
        $copy = $this->CopyContentFrom();
        return $copy && $copy->exists() && $copy->hasField($field);
    }

    /**
     * Return the "casting helper" (a piece of PHP code that when evaluated creates a casted value object) for a field
     * on this object.
     *
     * @param string $field
     * @return string
     */
    public function castingHelper($field)
    {
        $copy = $this->CopyContentFrom();
        if ($copy && $copy->exists() && ($helper = $copy->castingHelper($field))) {
            return $helper;
        }
        return parent::castingHelper($field);
    }

    /**
     * {@inheritdoc}
     */
    public function allMethodNames($custom = false)
    {
        $methods = parent::allMethodNames($custom);

        if ($copy = $this->CopyContentFrom()) {
            $methods = array_merge($methods, $copy->allMethodNames($custom));
        }

        return $methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getControllerName()
    {
        if ($copy = $this->CopyContentFrom()) {
            return $copy->getControllerName();
        }

        return parent::getControllerName();
    }
}
