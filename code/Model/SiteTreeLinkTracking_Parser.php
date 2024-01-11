<?php
namespace SilverStripe\CMS\Model;

use DOMElement;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Parsers\HTMLValue;

/**
 * A helper object for extracting information about links.
 */
class SiteTreeLinkTracking_Parser
{

    /**
     * Finds the links that are of interest for the link tracking automation. Checks for brokenness and attaches
     * extracted metadata so consumers can decide what to do with the DOM element (provided as DOMReference).
     *
     * @param HTMLValue $htmlValue Object to parse the links from.
     * @return array Associative array containing found links with the following field layout:
     *        Type: string, name of the link type
     *        Target: any, a reference to the target object, depends on the Type
     *        Anchor: string, anchor part of the link
     *        DOMReference: DOMElement, reference to the link to apply changes.
     *        Broken: boolean, a flag highlighting whether the link should be treated as broken.
     */
    public function process(HTMLValue $htmlValue)
    {
        $results = [];

        $links = $htmlValue->getElementsByTagName('a');
        if (!$links) {
            return $results;
        }

        /** @var DOMElement $link */
        foreach ($links as $link) {
            if (!$link->hasAttribute('href')) {
                continue;
            }

            $href = $link->getAttribute('href');
            if (Director::is_site_url($href)) {
                $href = Director::makeRelative($href);
            }

            // Definitely broken links.
            if ($href == '' || $href[0] == '/') {
                $results[] = [
                    'Type' => 'broken',
                    'Target' => null,
                    'Anchor' => null,
                    'DOMReference' => $link,
                    'Broken' => true
                ];

                continue;
            }

            // Link to a page on this site.
            $matches = [];
            if (preg_match('/\[sitetree_link(?:\s*|%20|,)?id=(?<id>[0-9]+)\](#(?<anchor>.*))?/i', $href ?? '', $matches)) {
                // Check if page link is broken
                $page = DataObject::get_by_id(SiteTree::class, $matches['id']);
                if (!$page) {
                    // Page doesn't exist.
                    $broken = true;
                } elseif (!empty($matches['anchor'])) {
                    // Ensure anchor isn't broken on target page
                    $broken = !in_array($matches['anchor'], $page->getAnchorsOnPage() ?? []);
                } else {
                    $broken = false;
                }

                $results[] = [
                    'Type' => 'sitetree',
                    'Target' => $matches['id'],
                    'Anchor' => empty($matches['anchor']) ? null : $matches['anchor'],
                    'DOMReference' => $link,
                    'Broken' => $broken
                ];

                continue;
            }

            // Local anchor.
            if (preg_match('/^#(.*)/i', $href ?? '', $matches)) {
                $anchor = preg_quote($matches[1] ?? '', '#');
                $results[] = [
                    'Type' => 'localanchor',
                    'Target' => null,
                    'Anchor' => $matches[1],
                    'DOMReference' => $link,
                    'Broken' => !preg_match("#(name|id)=\"{$anchor}\"#", $htmlValue->getContent() ?? '')
                ];

                continue;
            }
        }
        return $results;
    }
}
