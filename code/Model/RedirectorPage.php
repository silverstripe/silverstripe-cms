<?php

namespace SilverStripe\CMS\Model;

use Page;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Versioned\Versioned;

/**
 * A redirector page redirects when the page is visited.
 *
 * @property string $RedirectionType Either 'Internal','External' or 'File'
 * @property string $ExternalURL URL to redirect to if $RedirectionType is 'External'
 * @property int $LinkToID
 * @method SiteTree LinkTo()
 * @method File LinkToFile()
 */
class RedirectorPage extends Page
{
    private static $description = 'Redirects requests to another location';

    private static $icon_class = 'font-icon-p-redirect';

    private static $show_stage_link = false;

    private static $show_live_link = false;

    private static $db = [
        "RedirectionType" => "Enum('Internal,External,File','Internal')",
        "ExternalURL" => "Varchar(2083)" // 2083 is the maximum length of a URL in Internet Explorer.
    ];

    private static $defaults = [
        "RedirectionType" => "Internal"
    ];

    private static $has_one = [
        "LinkTo" => SiteTree::class,
        "LinkToFile" => File::class,
    ];

    private static $table_name = 'RedirectorPage';

    /**
     * Returns this page if the redirect is external, otherwise
     * returns the target page or file.
     *
     * @return SiteTree|File
     */
    public function ContentSource()
    {
        if ($this->RedirectionType == 'Internal') {
            return $this->LinkTo();
        } elseif ($this->RedirectionType == 'File') {
            return $this->LinkToFile();
        } else {
            return $this;
        }
    }

    /**
     * Return the the link that should be used for this redirector page, in navigation, etc.
     * If the redirectorpage has been appropriately configured, then it will return the redirection
     * destination, to prevent unnecessary 30x redirections.  However, if it's misconfigured, then
     * it will return a link to itself, which will then display an error message.
     *
     * @param string $action
     * @return string
     */
    public function Link($action = null)
    {
        $link = $this->redirectionLink();

        if ($link) {
            return $link;
        } else {
            return $this->regularLink($action);
        }
    }

    /**
     * Return the normal link directly to this page.  Once you visit this link, a 30x redirection
     * will take you to your final destination.
     *
     * @param string $action
     * @return string
     */
    public function regularLink($action = null)
    {
        return parent::Link($action);
    }

    /**
     * Return the link that we should redirect to.
     * Only return a value if there is a legal redirection destination.
     *
     * @return string
     */
    public function redirectionLink()
    {
        // Check external redirect
        if ($this->RedirectionType == 'External') {
            $result = $this->ExternalURL ?: null;

            $this->extend('updateRedirectionLink', $result);

            return $result;
        } elseif ($this->RedirectionType == 'File') {
            $result = $this->LinkToFile()->exists() ? $this->LinkToFile()->getURL() : null;

            $this->extend('updateRedirectionLink', $result);

            return $result;
        }

        // Check internal redirect
        $linkTo = $this->LinkToID ? SiteTree::get()->byID($this->LinkToID) : null;

        if (empty($linkTo)) {
            $link = null;
        } elseif ($this->ID == $linkTo->ID) {
            // We shouldn't point to ourselves
            $link = null;
        } elseif ($linkTo instanceof RedirectorPage) {
            // If we're linking to another redirectorpage then just return the
            // URLSegment, to prevent a cycle of redirector
            // pages from causing an infinite loop.  Instead, they will cause
            // a 30x redirection loop in the browser, but
            // this can be handled sufficiently gracefully by the browser.
            $link = $linkTo->regularLink();
        } else {
            // For all other pages, just return the link of the page.
            $link = $linkTo->Link();
        }

        $this->extend('updateRedirectionLink', $link);

        return $link;
    }

    public function syncLinkTracking()
    {
        if ($this->RedirectionType == 'Internal') {
            if ($this->LinkToID) {
                $this->HasBrokenLink = Versioned::get_by_stage(SiteTree::class, Versioned::DRAFT)
                    ->filter('ID', $this->LinkToID)
                    ->count() === 0;
            } else {
                $this->HasBrokenLink = true;
            }
        } elseif ($this->RedirectionType == 'File') {
            if ($this->LinkToFileID) {
                $this->HasBrokenLink = Versioned::get_by_stage(File::class, Versioned::DRAFT)
                    ->filter('ID', $this->LinkToFileID)
                    ->count() === 0;
            } else {
                $this->HasBrokenLink = true;
            }
        } else {
            $this->HasBrokenLink = false;
        }
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->ExternalURL && substr($this->ExternalURL ?? '', 0, 2) !== '//') {
            $urlParts = parse_url($this->ExternalURL ?? '');
            if ($urlParts) {
                if (empty($urlParts['scheme'])) {
                    // no scheme, assume http
                    $this->ExternalURL = 'http://' . $this->ExternalURL;
                } elseif (!in_array($urlParts['scheme'], [
                    'http',
                    'https',
                ])) {
                    // we only allow http(s) urls
                    $this->ExternalURL = '';
                }
            } else {
                // malformed URL to reject
                $this->ExternalURL = '';
            }
        }
    }

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName('Content', true);

            // Remove all metadata fields, does not apply for redirector pages
            $fields->removeByName('Metadata');

            $fields->addFieldsToTab(
                'Root.Main',
                [
                    new HeaderField('RedirectorDescHeader', _t(__CLASS__.'.HEADER', "This page will redirect users to another page")),
                    new OptionsetField(
                        "RedirectionType",
                        _t(__CLASS__.'.REDIRECTTO', "Redirect to"),
                        [
                            "Internal" => _t(__CLASS__.'.REDIRECTTOPAGE', "A page on your website"),
                            "External" => _t(__CLASS__.'.REDIRECTTOEXTERNAL', "Another website"),
                            "File" => _t(__CLASS__.'.REDIRECTTOFILE', "A file on your website"),
                        ],
                        "Internal"
                    ),
                    new TreeDropdownField(
                        "LinkToID",
                        _t(__CLASS__.'.YOURPAGE', "Page on your website"),
                        SiteTree::class
                    ),
                    new UploadField('LinkToFile', _t(__CLASS__.'.FILE', "File")),
                    new TextField("ExternalURL", _t(__CLASS__.'.OTHERURL', "Other website URL"))
                ]
            );
        });

        return parent::getCMSFields();
    }

    // Don't cache RedirectorPages
    public function subPagesToCache()
    {
        return [];
    }
}
