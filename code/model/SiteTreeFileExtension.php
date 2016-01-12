<?php
/**
 * @package cms
 * @subpackage model
 */
class SiteTreeFileExtension extends DataExtension {

	private static $belongs_many_many = array(
		'BackLinkTracking' => 'SiteTree'
	);

	public function updateCMSFields(FieldList $fields) {
		$fields->insertAfter(
			ReadonlyField::create(
				'BackLinkCount',
				_t('AssetTableField.BACKLINKCOUNT', 'Used on:'),
				$this->BackLinkTracking()->Count() . ' ' . _t('AssetTableField.PAGES', 'page(s)'))
			->addExtraClass('cms-description-toggle')
			->setDescription($this->BackLinkHTMLList()),
			'LastEdited'
		);
	}

	/**
	 * Generate an HTML list which provides links to where a file is used.
	 *
	 * @return string
	 */
	public function BackLinkHTMLList() {
		$html = '<em>' . _t('SiteTreeFileExtension.BACKLINK_LIST_DESCRIPTION', 'This list shows all pages where the file has been added through a WYSIWYG editor.') . '</em>';
		$html .= '<ul>';

		foreach ($this->BackLinkTracking() as $backLink) {
			$listItem = '<li>';

			// Add the page link
			$listItem .= '<a href="' . $backLink->Link() . '" target="_blank">' . Convert::raw2xml($backLink->MenuTitle) . '</a> &ndash; ';

			// Add the CMS link
			$listItem .= '<a href="' . $backLink->CMSEditLink() . '">' . _t('SiteTreeFileExtension.EDIT', 'Edit') . '</a>';

			$html .= $listItem . '</li>';
		}

		return $html .= '</ul>';
	}

	/**
	 * Extend through {@link updateBackLinkTracking()} in your own {@link Extension}.
	 *
	 * @param string|array $filter
	 * @param string $sort
	 * @param string $join
	 * @param string $limit
	 * @return ManyManyList
	 */
	public function BackLinkTracking($filter = null, $sort = null, $join = null, $limit = null) {
		if($filter !== null || $sort !== null || $join !== null || $limit !== null) {
			Deprecation::notice('4.0', 'The $filter, $sort, $join and $limit parameters for
				SiteTreeFileExtension::BackLinkTracking() have been deprecated.
				Please manipluate the returned list directly.', Deprecation::SCOPE_GLOBAL);
		}
		
		if(class_exists("Subsite")){
			$rememberSubsiteFilter = Subsite::$disable_subsite_filter;
			Subsite::disable_subsite_filter(true);
		}

		if($filter || $sort || $join || $limit) {
			Deprecation::notice('4.0', 'The $filter, $sort, $join and $limit parameters for
				SiteTreeFileExtension::BackLinkTracking() have been deprecated.
				Please manipluate the returned list directly.', Deprecation::SCOPE_GLOBAL);
		}
		
		$links = $this->owner->getManyManyComponents('BackLinkTracking');
		if($this->owner->ID) {
			$links = $links
				->where($filter)
				->sort($sort)
				->limit($limit);
		}
		$this->owner->extend('updateBackLinkTracking', $links);
		
		if(class_exists("Subsite")){
			Subsite::disable_subsite_filter($rememberSubsiteFilter);
		}
		
		return $links;
	}
	
	/**
	 * @todo Unnecessary shortcut for AssetTableField, coupled with cms module.
	 *
	 * @return integer
	 */
	public function BackLinkTrackingCount() {
		$pages = $this->owner->BackLinkTracking();
		if($pages) {
			return $pages->Count();
		} else {
			return 0;
		}
	}
	
	/**
	 * Updates link tracking.
	 */
	public function onAfterDelete() {
		// We query the explicit ID list, because BackLinkTracking will get modified after the stage
		// site does its thing
		$brokenPageIDs = $this->owner->BackLinkTracking()->column("ID");
		if($brokenPageIDs) {
			$origStage = Versioned::current_stage();

			// This will syncLinkTracking on draft
			Versioned::reading_stage('Stage');
			$brokenPages = DataObject::get('SiteTree')->byIDs($brokenPageIDs);
			foreach($brokenPages as $brokenPage) $brokenPage->write();

			// This will syncLinkTracking on published
			Versioned::reading_stage('Live');
			$liveBrokenPages = DataObject::get('SiteTree')->byIDs($brokenPageIDs);
			foreach($liveBrokenPages as $brokenPage) {
				$brokenPage->write();
			}

			Versioned::reading_stage($origStage);
		}
	}
	
	/**
	 * Rewrite links to the $old file to now point to the $new file.
	 *
	 * @uses SiteTree->rewriteFileURL()
	 *
	 * @param string $old File path relative to the webroot
	 * @param string $new File path relative to the webroot
	 */
	public function updateLinks($old, $new) {
		if(class_exists('Subsite')) Subsite::disable_subsite_filter(true);
	
		$pages = $this->owner->BackLinkTracking();

		$summary = "";
		if($pages) {
			foreach($pages as $page) $page->rewriteFileURL($old,$new);
		}
		
		if(class_exists('Subsite')) Subsite::disable_subsite_filter(false);
	}
	
}
