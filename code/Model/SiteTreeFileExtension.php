<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\Assets\File;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\SSViewer;

/**
 * @deprecated 4.2..5.0 Link tracking is baked into File class now
 * @property File $owner
 */
class SiteTreeFileExtension extends DataExtension
{
    private static $casting = [
        'BackLinkHTMLList' => 'HTMLFragment'
    ];

    /**
     * Generate an HTML list which provides links to where a file is used.
     *
     * @return string
     */
    public function BackLinkHTMLList()
    {
        $viewer = SSViewer::create(["type" => "Includes", self::class . "_description"]);
        return $viewer->process($this->owner);
    }
}
