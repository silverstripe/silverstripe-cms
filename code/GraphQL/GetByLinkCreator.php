<?php

namespace SilverStripe\CMS\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\GraphQL\QueryCreator;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;

class GetByLinkCreator extends QueryCreator
{
    /**
     * @return callable|Type|string
     */
    public function type()
    {
        return $this->manager->getType(StaticSchema::inst()->typeNameForDataObject(SiteTree::class));
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'getPageByLink',
            'description' => 'Allows quering of a SiteTree record by its URL',
        ];
    }

    /**
     * @return array
     */
    public function args()
    {
        return [
            'Link' => [
                'name' => 'Link',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The link to the page, without the base URL, e.g. "path/to/page"',
            ]
        ];
    }

    /**
     * @param $obj
     * @param array $args
     * @return SiteTree|null
     */
    public function resolve($obj, array $args = [])
    {
        $link = $args['Link'] ?? null;

        return $link ? SiteTree::get_by_link($link) : null;
    }
}
