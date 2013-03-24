<?php
/**
 * A redirector page redirects when the page is visited.
 *
 * @package cms
 * @subpackage content
 */
class RedirectorPage extends Page {
	private static $description = 'Redirects to a different internal page';
	
	private static $db = array(
		"RedirectionType" => "Enum('Internal,External','Internal')",
		"ExternalURL" => "Varchar(2083)" // 2083 is the maximum length of a URL in Internet Explorer.
	);
	
	private static $defaults = array(
		"RedirectionType" => "Internal"
	);
	
	private static $has_one = array(
		"LinkTo" => "SiteTree",
	);
	
	private static $many_many = array(
	);
	
	/**
	 * Returns this page if the redirect is external, otherwise
	 * returns the target page.
	 * @return SiteTree
	 */
	public function ContentSource() {
		if($this->RedirectionType == 'Internal') {
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
	 */
	public function Link() {
		if($link = $this->redirectionLink()) return $link;
		else return $this->regularLink();
	}
	
	/**
	 * Return the normal link directly to this page.  Once you visit this link, a 30x redirection
	 * will take you to your final destination.
	 */
	public function regularLink($action = null) {
		return parent::Link($action);
	}
	
	/**
	 * Return the link that we should redirect to.
	 * Only return a value if there is a legal redirection destination.
	 */
	public function redirectionLink() {
		if($this->RedirectionType == 'External') {
			if($this->ExternalURL) {
				return $this->ExternalURL;
			}
			
		} else {
			$linkTo = $this->LinkToID ? DataObject::get_by_id("SiteTree", $this->LinkToID) : null;

			if($linkTo) {
				// We shouldn't point to ourselves - that would create an infinite loop!  Return null since we have a
				// bad configuration
				if($this->ID == $linkTo->ID) {
					return null;
			
				// If we're linking to another redirectorpage then just return the URLSegment, to prevent a cycle of redirector
				// pages from causing an infinite loop.  Instead, they will cause a 30x redirection loop in the browser, but
				// this can be handled sufficiently gracefully by the browser.
				} elseif($linkTo instanceof RedirectorPage) {
					return $linkTo->regularLink();

				// For all other pages, just return the link of the page.
				} else {
					return $linkTo->Link();
				}
			}
		}
	}
	
	public function syncLinkTracking() {
		if ($this->RedirectionType == 'Internal') {
			if($this->LinkToID) {
				$this->HasBrokenLink = DataObject::get_by_id('SiteTree', $this->LinkToID) ? false : true;
			} else {
				// An incomplete redirector page definitely has a broken link
				$this->HasBrokenLink = true;
			}
		} else {
			// TODO implement checking of a remote site
			$this->HasBrokenLink = false;
		}
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		// Prefix the URL with "http://" if no prefix is found
		if($this->ExternalURL && (strpos($this->ExternalURL, '://') === false)) {
			$this->ExternalURL = 'http://' . $this->ExternalURL;
		}
	}

	public function getCMSFields() {
		Requirements::javascript(CMS_DIR . '/javascript/RedirectorPage.js');
		
		$fields = parent::getCMSFields();
		$fields->removeByName('Content', true);
		
		// Remove all metadata fields, does not apply for redirector pages
		$fields->removeByName('Metadata');
		
		$fields->addFieldsToTab('Root.Main',
			array(
				new HeaderField('RedirectorDescHeader',_t('RedirectorPage.HEADER', "This page will redirect users to another page")),
				new OptionsetField(
					"RedirectionType", 
					_t('RedirectorPage.REDIRECTTO', "Redirect to"), 
					array(
						"Internal" => _t('RedirectorPage.REDIRECTTOPAGE', "A page on your website"),
						"External" => _t('RedirectorPage.REDIRECTTOEXTERNAL', "Another website"),
					), 
					"Internal"
				),
				new TreeDropdownField(	
					"LinkToID", 
					_t('RedirectorPage.YOURPAGE', "Page on your website"), 
					"SiteTree"
				),
				new TextField("ExternalURL", _t('RedirectorPage.OTHERURL', "Other website URL"))
			)
		);
		
		return $fields;
	}
	
	// Don't cache RedirectorPages
	public function subPagesToCache() {
		return array();
	}
}

/**
 * Controller for the {@link RedirectorPage}.
 * @package cms
 * @subpackage content
 */
class RedirectorPage_Controller extends Page_Controller {

	public function init() {
		parent::init();

		if($link = $this->redirectionLink()) {
			$this->redirect($link, 301);
			return;
		}
	}
	
	/**
	 * If we ever get this far, it means that the redirection failed.
	 */
	public function Content() {
		return "<p class=\"message-setupWithoutRedirect\">" .
			_t('RedirectorPage.HASBEENSETUP', 'A redirector page has been set up without anywhere to redirect to.') .
			"</p>";
	}
}
