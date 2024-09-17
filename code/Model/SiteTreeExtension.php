<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Core\Extension;

/**
 * Plug-ins for additional functionality in your SiteTree classes.
 *
 * @template T of SiteTree
 * @extends DataExtension<T>
 * @deprecated 5.3.0 Subclass SilverStripe\Core\Extension\Extension instead
 */
abstract class SiteTreeExtension extends DataExtension
{
    public function __construct()
    {
        // Wrapping with Deprecation::withSuppressedNotice() to avoid triggering deprecation notices
        // as we are unable to update existing subclasses of this class until a new major
        // unless we add in the pointless empty methods that are in this class
        Deprecation::withSuppressedNotice(function () {
            $class = Extension::class;
            Deprecation::notice('5.3.0', "Subclass $class instead", Deprecation::SCOPE_CLASS);
        });
        parent::__construct();
    }

    /**
     * Hook called before the page's {@link Versioned::publishSingle()} action is completed
     *
     * @param SiteTree &$original The current Live SiteTree record prior to publish
     */
    public function onBeforePublish(&$original)
    {
    }

    /**
     * Hook called after the page's {@link Versioned::publishSingle()} action is completed
     *
     * @param SiteTree &$original The current Live SiteTree record prior to publish
     */
    public function onAfterPublish(&$original)
    {
    }

    /**
     * Hook called before the page's {@link Versioned::doUnpublish()} action is completed
     */
    public function onBeforeUnpublish()
    {
    }


    /**
     * Hook called after the page's {@link SiteTree::doUnpublish()} action is completed
     */
    public function onAfterUnpublish()
    {
    }

    /**
     * Hook called to determine if a user may add children to this SiteTree object
     *
     * @see SiteTree::canAddChildren()
     *
     * @param Member $member The member to check permission against, or the currently
     * logged in user
     * @return boolean|null Return false to deny rights, or null to yield to default
     */
    public function canAddChildren($member)
    {
    }

    /**
     * Hook called to determine if a user may publish this SiteTree object
     *
     * @see SiteTree::canPublish()
     *
     * @param Member $member The member to check permission against, or the currently
     * logged in user
     * @return boolean|null Return false to deny rights, or null to yield to default
     */
    public function canPublish($member)
    {
    }

    /**
     * Hook called to modify the $base url of this page, with a given $action,
     * before {@link SiteTree::RelativeLink()} calls {@link Controller::join_links()}
     * on the $base and $action
     *
     * @param string &$link The URL of this page relative to siteroot including
     * the action
     * @param string $base The URL of this page relative to siteroot, not including
     * the action
     * @param string|boolean $action The action or subpage called on this page.
     * (Legacy support) If this is true, then do not reduce the 'home' urlsegment
     * to an empty link
     */
    public function updateRelativeLink(&$link, $base, $action)
    {
    }
}
