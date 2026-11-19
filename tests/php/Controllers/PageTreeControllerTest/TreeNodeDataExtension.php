<?php

namespace SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TreeNodeDataExtension extends Extension implements TestOnly
{
    protected function updateTreeNodeData(array &$data, DataObject $node, DataObject $parent): void
    {
        $data['tooltipSeen'] = $data['tooltip'];
    }
}
