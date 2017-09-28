<?php

namespace SilverStripe\CMS\Forms;

use Page;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\TextField;

/**
 * Assists with selecting anchors on a given page
 */
class AnchorSelectorField extends TextField
{
    protected $schemaComponent = 'AnchorSelectorField';

    private static $allowed_actions = [
        'anchors',
    ];

    private static $url_handlers = [
        'anchors/$PageID' => 'anchors',
    ];

    public function getSchemaDataDefaults()
    {
        $schema = parent::getSchemaDataDefaults();
        $schema['data']['endpoint'] = $this->Link('anchors/:id');
        return $schema;
    }

    /**
     * Find all anchors available on the given page.
     *
     * @param HTTPRequest $request
     * @return array
     */
    public function anchors(HTTPRequest $request)
    {
        $id = (int)$this->getRequest()->param('PageID');
        $anchors = $this->getAnchorsInPage($id);
        return json_encode($anchors);
    }

    /**
     * Get anchors in the given page ID
     *
     * @param int $id
     * @return array
     */
    protected function getAnchorsInPage($id)
    {
        // Check page is accessible
        $page = Page::get()->byID($id);
        if (!$page || !$page->canView()) {
            return [];
        }

        // Similar to the regex found in HtmlEditorField.js / getAnchors method.
        $parseSuccess = preg_match_all(
            "/\\s+(name|id)\\s*=\\s*([\"'])([^\\2\\s>]*?)\\2|\\s+(name|id)\\s*=\\s*([^\"']+)[\\s +>]/im",
            $page->Content,
            $matches
        );
        if (!$parseSuccess) {
            return [];
        }

        // Cleanup results
        return array_values(array_unique(array_filter(array_merge($matches[3], $matches[5]))));
    }
}
