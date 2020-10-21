<?php


namespace SilverStripe\CMS\GraphQL;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolverProvider;

if (!class_exists(DefaultResolverProvider::class)) {
    return;
}

class Resolver extends DefaultResolverProvider
{
    public static function resolveGetPageByLink($obj, array $args = [])
    {
        return SiteTree::get_by_link($args['link']);
    }
}
