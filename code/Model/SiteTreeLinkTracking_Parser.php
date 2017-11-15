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
        $results = array();

        // @todo - Should be calling getElementsByTagName on DOMDocument?
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
                $results[] = array(
                    'Type' => 'broken',
                    'Target' => null,
                    'Anchor' => null,
                    'DOMReference' => $link,
                    'Broken' => true
                );

                continue;
            }

            // Link to a page on this site.
            $matches = array();
            if (preg_match('/\[sitetree_link(?:\s*|%20|,)?id=(?<id>[0-9]+)\](#(?<anchor>.*))?/i', $href, $matches)) {
                $page = DataObject::get_by_id(SiteTree::class, $matches['id']);
                $broken = false;

                if (!$page) {
                    // Page doesn't exist.
                    $broken = true;
                } else {
                    if (!empty($matches['anchor'])) {
                        $anchor = preg_quote($matches['anchor'], '/');

                        if (!preg_match("/(name|id)=\"{$anchor}\"/", $page->Content)) {
                            // Broken anchor on the target page.
                            $broken = true;
                        }
                    }
                }

                $results[] = array(
                    'Type' => 'sitetree',
                    'Target' => $matches['id'],
                    'Anchor' => empty($matches['anchor']) ? null : $matches['anchor'],
                    'DOMReference' => $link,
                    'Broken' => $broken
                );

                continue;
            }

            // Link to a file on this site.
            $matches = array();
            if (preg_match('/\[file_link(?:\s*|%20|,)?id=(?<id>[0-9]+)/i', $href, $matches)) {
                $results[] = array(
                    'Type' => 'file',
                    'Target' => $matches['id'],
                    'Anchor' => null,
                    'DOMReference' => $link,
                    'Broken' => !DataObject::get_by_id('SilverStripe\\Assets\\File', $matches['id'])
                );

                continue;
            }

            // Local anchor.
            $matches = array();
            if (preg_match('/^#(.*)/i', $href, $matches)) {
                $anchor = preg_quote($matches[1], '#');
                $results[] = array(
                    'Type' => 'localanchor',
                    'Target' => null,
                    'Anchor' => $matches[1],
                    'DOMReference' => $link,
                    'Broken' => !preg_match("#(name|id)=\"{$anchor}\"#", $htmlValue->getContent())
                );

                continue;
            }
        }

        // Find all [image ] shortcodes (will be inline, not inside attributes)
        $content = $htmlValue->getContent();
        if (preg_match_all('/\[image([^\]]+)\bid=(["])?(?<id>\d+)\D/i', $content, $matches)) {
            foreach ($matches['id'] as $id) {
                $results[] = array(
                    'Type' => 'image',
                    'Target' => (int)$id,
                    'Anchor' => null,
                    'DOMReference' => null,
                    'Broken' => !DataObject::get_by_id('SilverStripe\\Assets\\Image', (int)$id)
                );
            }
        }
        return $results;
    }
}
