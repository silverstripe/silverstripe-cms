<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\Assets\File;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\SSViewer;

/**
 * @deprecated 1.2..2.0 Link tracking is baked into File class now
 * @property File $owner
 */
class SiteTreeFileExtension extends DataExtension
{
    private static $belongs_many_many = array(
        'BackLinkTracking' => SiteTree::class . '.ImageTracking' // {@see SiteTreeLinkTracking}
    );

    private static $casting = array(
        'BackLinkHTMLList' => 'HTMLFragment'
    );

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
