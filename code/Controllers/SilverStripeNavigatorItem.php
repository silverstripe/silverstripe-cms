<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Member;
use SilverStripe\View\ViewableData;

/**
 * Navigator items are links that appear in the $SilverStripeNavigator bar.
 * To add an item, extend this class - it will be automatically picked up.
 * When instanciating items manually, please ensure to call {@link canView()}.
 */
abstract class SilverStripeNavigatorItem extends ViewableData
{

    /**
     * @param DataObject|CMSPreviewable
     */
    protected $record;

    /** @var string */
    protected $recordLink;

    /**
     * @param DataObject|CMSPreviewable $record
     */
    public function __construct(CMSPreviewable $record)
    {
        parent::__construct();
        $this->record = $record;
    }

    /**
     * @return string HTML, mostly a link - but can be more complex as well.
     * For example, a "future state" item might show a date selector.
     */
    abstract public function getHTML();

    /**
     * @return string
     * Get the Title of an item
     */
    abstract public function getTitle();

    /**
     * Machine-friendly name.
     *
     * @return string
     */
    public function getName()
    {
        return substr(static::class, strpos(static::class, '_') + 1);
    }

    /**
     * Optional link to a specific view of this record.
     * Not all items are simple links, please use {@link getHTML()}
     * to represent an item in markup unless you know what you're doing.
     *
     * @return string
     */
    public function getLink()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return null;
    }

    /**
     * @return DataObject
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->config()->get('priority');
    }

    /**
     * As items might convey different record states like a "stage" or "live" table,
     * an item can be active (showing the record in this state).
     *
     * @return boolean
     */
    public function isActive()
    {
        return false;
    }

    /**
     * Filters items based on member permissions or other criteria,
     * such as if a state is generally available for the current record.
     *
     * @param Member $member
     * @return Boolean
     */
    public function canView($member = null)
    {
        return true;
    }

    /**
     * Counts as "archived" if the current record is a different version from both live and draft.
     *
     * @return boolean
     */
    public function isArchived()
    {
        /** @var Versioned|DataObject $record */
        $record = $this->record;
        if (!$record->hasExtension(Versioned::class) || !$record->hasStages()) {
            return false;
        }

        if (!isset($record->_cached_isArchived)) {
            $baseClass = $record->baseClass();
            $currentDraft = Versioned::get_by_stage($baseClass, Versioned::DRAFT)->byID($record->ID);
            $currentLive = Versioned::get_by_stage($baseClass, Versioned::LIVE)->byID($record->ID);

            $record->_cached_isArchived = (
                (!$currentDraft || ($currentDraft && $record->Version != $currentDraft->Version))
                && (!$currentLive || ($currentLive && $record->Version != $currentLive->Version))
            );
        }

        return $record->_cached_isArchived;
    }
}
