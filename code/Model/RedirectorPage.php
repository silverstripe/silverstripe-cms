<?php

namespace SilverStripe\CMS\Model;

use Page;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Versioned\Versioned;

/**
 * A redirector page redirects when the page is visited.
 *
 * @property string $RedirectionType Either 'Internal' or 'External'
 * @property string $ExternalURL URL to redirect to if $RedirectionType is 'External'
 * @property int $LinkToID
 * @method SiteTree LinkTo() Page to link to if $RedirectionType is 'Internal'
 */
class RedirectorPage extends Page
{
    private static $description = 'Redirects to an internal page or an external URL';

    private static $db = array(
        "RedirectionType" => "Enum('Internal,External','Internal')",
        "ExternalURL" => "Varchar(2083)" // 2083 is the maximum length of a URL in Internet Explorer.
    );

    private static $defaults = array(
        "RedirectionType" => "Internal"
    );

    private static $has_one = array(
        "LinkTo" => SiteTree::class,
    );

    private static $table_name = 'RedirectorPage';

    /**
     * Returns this page if the redirect is external, otherwise
     * returns the target page.
     * @return SiteTree
     */
    public function ContentSource()
    {
        if ($this->RedirectionType == 'Internal') {
            return $this->LinkTo();
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
     */
    public function redirectionLink()
    {
        // Check external redirect
        if ($this->RedirectionType == 'External') {
            return $this->ExternalURL ?: null;
        }

        // Check internal redirect
        /** @var SiteTree $linkTo */
        $linkTo = $this->LinkToID ? SiteTree::get()->byID($this->LinkToID) : null;
        if (empty($linkTo)) {
            return null;
        }

        // We shouldn't point to ourselves - that would create an infinite loop!  Return null since we have a
        // bad configuration
        if ($this->ID == $linkTo->ID) {
            return null;
        }

        // If we're linking to another redirectorpage then just return the URLSegment, to prevent a cycle of redirector
        // pages from causing an infinite loop.  Instead, they will cause a 30x redirection loop in the browser, but
        // this can be handled sufficiently gracefully by the browser.
        if ($linkTo instanceof RedirectorPage) {
            return $linkTo->regularLink();
        }

        // For all other pages, just return the link of the page.
        return $linkTo->Link();
    }

    public function syncLinkTracking()
    {
        if ($this->RedirectionType == 'Internal') {
            if ($this->LinkToID) {
                $this->HasBrokenLink = Versioned::get_by_stage(SiteTree::class, Versioned::DRAFT)
                    ->filter('ID', $this->LinkToID)
                    ->count() === 0;
            } else {
                // An incomplete redirector page definitely has a broken link
                $this->HasBrokenLink = true;
            }
        } else {
            // TODO implement checking of a remote site
            $this->HasBrokenLink = false;
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->ExternalURL && substr($this->ExternalURL, 0, 2) !== '//') {
            $urlParts = parse_url($this->ExternalURL);
            if ($urlParts) {
                if (empty($urlParts['scheme'])) {
                    // no scheme, assume http
                    $this->ExternalURL = 'http://' . $this->ExternalURL;
                } elseif (!in_array($urlParts['scheme'], array(
                    'http',
                    'https',
                ))) {
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
                array(
                    new HeaderField('RedirectorDescHeader', _t(__CLASS__.'.HEADER', "This page will redirect users to another page")),
                    new OptionsetField(
                        "RedirectionType",
                        _t(__CLASS__.'.REDIRECTTO', "Redirect to"),
                        array(
                            "Internal" => _t(__CLASS__.'.REDIRECTTOPAGE', "A page on your website"),
                            "External" => _t(__CLASS__.'.REDIRECTTOEXTERNAL', "Another website"),
                        ),
                        "Internal"
                    ),
                    new TreeDropdownField(
                        "LinkToID",
                        _t(__CLASS__.'.YOURPAGE', "Page on your website"),
                        SiteTree::class
                    ),
                    new TextField("ExternalURL", _t(__CLASS__.'.OTHERURL', "Other website URL"))
                )
            );
        });
        return parent::getCMSFields();
    }

    // Don't cache RedirectorPages
    public function subPagesToCache()
    {
        return array();
    }
}
