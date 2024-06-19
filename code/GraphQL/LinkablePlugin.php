<?php


namespace SilverStripe\CMS\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\QueryFilter;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

if (!interface_exists(ModelQueryPlugin::class)) {
    return;
}

class LinkablePlugin implements ModelQueryPlugin
{
    use Configurable;
    use Injectable;

    const IDENTIFIER = 'getByLink';

    /**
     * @var string
     * @config
     */
    private static $single_field_name = 'link';

    /**
     * @var string
     * @config
     */
    private static $list_field_name = 'links';

    /**
     * @var array
     */
    private static $resolver = [__CLASS__, 'applyLinkFilter'];

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return LinkablePlugin::IDENTIFIER;
    }

    /**
     * @param ModelQuery $query
     * @param Schema $schema
     * @param array $config
     */
    public function apply(ModelQuery $query, Schema $schema, array $config = []): void
    {
        $class = $query->getModel()->getSourceClass();
        // Only site trees have the get_by_link capability
        if ($class !== SiteTree::class && !is_subclass_of($class, SiteTree::class)) {
            return;
        }
        $singleFieldName = $this->config()->get('single_field_name');
        $listFieldName = $this->config()->get('list_field_name');
        $fieldName = $query->isList() ? $listFieldName : $singleFieldName;
        $type = $query->isList() ? '[String]' : 'String';
        $query->addArg($fieldName, $type);
        $query->addResolverAfterware(
            $config['resolver'] ?? static::config()->get('resolver')
        );
    }

    /**
     * @param array $context
     * @return callable
     */
    public static function applyLinkFilter($obj, array $args, array $context, ResolveInfo $info)
    {
        $singleFieldName = static::config()->get('single_field_name');
        $listFieldName = static::config()->get('list_field_name');
        $filterLink = $args['filter'][$singleFieldName] ?? ($args['filter'][$listFieldName] ?? null);
        $argLink = $args[$singleFieldName] ?? ($args[$listFieldName] ?? null);
        $linkData = $filterLink ?: $argLink;
        if (!$linkData) {
            return $obj;
        }
        // Normalise to an array for both cases. The readOne operation will get
        // ->first() run on it by the firstResult plugin.
        $links = is_array($linkData) ? $linkData : [$linkData];

        $result = ArrayList::create();

        foreach ($links as $link) {
            $page = SiteTree::get_by_link($link);
            if ($page) {
                $result->push($page);
            }
        }
        return $result;
    }
}
