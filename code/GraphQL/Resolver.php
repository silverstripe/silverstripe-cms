<?php


namespace SilverStripe\CMS\GraphQL;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\Deprecation;

/**
 * @deprecated 5.3.0 Will be moved to the silverstripe/graphql module in a future major release
 */
class Resolver
{
    public function __construct()
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.3.0', 'Will be moved to the silverstripe/graphql module', Deprecation::SCOPE_CLASS);
        });
    }

    public static function resolveGetPageByLink($obj, array $args = [])
    {
        return SiteTree::get_by_link($args['link']);
    }
}
