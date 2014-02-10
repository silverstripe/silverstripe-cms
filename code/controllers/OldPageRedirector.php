<?php

class OldPageRedirector extends Extension {

	/**
	 * On every URL that generates a 404, we'll capture it here and see if we can
	 * find an old URL that it should be redirecting to.
	 *
	 * @param SS_HTTPResponse $request The request object
	 * @throws SS_HTTPResponse_Exception
	 */
	public function onBeforeHTTPError404($request) {
		// Build up the request parameters
		$params = array_filter(array_values($request->allParams()), function($v) { return ($v !== NULL); });

		$getvars = $request->getVars();
		unset($getvars['url']);

		$page = self::find_old_page($params);

		if ($page) {
			$res = new SS_HTTPResponse();
			$res->redirect(
				Controller::join_links(
					$page,
					($getvars) ? '?' . http_build_query($getvars) : null
				), 301);
			throw new SS_HTTPResponse_Exception($res);
		}
	}

	/**
	 * Attempt to find an old/renamed page from some given the URL as an array
	 *
	 * @param array $params The array of URL, e.g. /foo/bar as array('foo', 'bar')
	 * @param SiteTree $parent The current parent in the recursive flow
	 * @param boolean $redirect Whether we've found an old page worthy of a redirect
	 *
	 * @return string|boolean False, or the new URL
	 */
	static public function find_old_page($params, $parent = null, $redirect = false) {
		$URL = Convert::raw2sql(array_shift($params));
		if (empty($URL)) { return false; }
		if ($parent) {
			$page = SiteTree::get()->filter(array('ParentID' => $parent->ID, 'URLSegment' => $URL))->First();
		} else {
			$page = SiteTree::get()->filter(array('URLSegment' => $URL))->First();
		}

		if (!$page) {
			// If we haven't found a candidate, lets resort to finding an old page with this URL segment
			// TODO: Rewrite using ORM syntax
			$query = new SQLQuery (
				'"RecordID"',
				'"SiteTree_versions"',
				"\"URLSegment\" = '$URL' AND \"WasPublished\" = 1" . ($parent ? ' AND "ParentID" = ' . $parent->ID : ''),
				'"LastEdited" DESC',
				null,
				null,
				1
			);
			$record = $query->execute()->first();
			if ($record) {
				$page = SiteTree::get()->byID($record['RecordID']);
				$redirect = true;
			}
		}

		if ($page && $page->canView()) {
			if (count($params)) {
				// We have to go deeper!
				$ret = self::find_old_page($params, $page, $redirect);
				if ($ret) {
					// A valid child page was found! We can return it
					return $ret;
				} else {
					// No valid page found.
					if ($redirect) {
						// If we had some redirect to be done, lets do it. imagine /foo/action -> /bar/action, we still want this redirect to happen if action isn't a page
						return $page->Link() . implode('/', $params);
					}
				}
			} else {
				// We've found the final, end all, page.
				return $page->Link();
			}
		}

		return false;
	}
}

