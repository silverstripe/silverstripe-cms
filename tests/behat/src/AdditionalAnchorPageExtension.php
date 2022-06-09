<?php

namespace SilverStripe\CMS\Tests\Behaviour;

use SilverStripe\Core\Extension;

class AdditionalAnchorPageExtension extends Extension
{
    public function updateAnchorsOnPage(array &$anchors): void
    {
        $anchors[] = 'dataobject-anchor';
    }
}
