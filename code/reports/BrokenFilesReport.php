<?php

/**
 * @package cms
 * @subpackage reports
 */
class BrokenFilesReport extends SS_Report {

	public function title() {
		return _t('SideReport.BROKENFILES',"Pages with broken files");
	}

	public function group() {
		return _t('SideReport.BrokenLinksGroupTitle', "Broken links reports");
	}

	public function sourceRecords($params = null) {
		// Get class names for page types that are not virtual pages or redirector pages
		$classes = array_diff(
			ClassInfo::subclassesFor('SiteTree'),
			ClassInfo::subclassesFor('VirtualPage'),
			ClassInfo::subclassesFor('RedirectorPage')
		);
		$classParams = DB::placeholders($classes);
		$classFilter = array(
			"\"ClassName\" IN ($classParams) AND \"HasBrokenFile\" = 1" => $classes
		);
		
		$stage = isset($params['OnLive']) ? 'Live' : 'Stage';
		return Versioned::get_by_stage('SiteTree', $stage, $classFilter);
	}

	public function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
		);
	}

	public function getParameterFields() {
		return new FieldList(
			new CheckboxField('OnLive', _t('SideReport.ParameterLiveCheckbox', 'Check live site'))
		);
	}
}

/**
 * @deprecated 3.2..4.0
 */
class SideReport_BrokenFiles extends BrokenFilesReport {
	public function __construct() {
		Deprecation::notice('4.0', 'Use BrokenFilesReport instead');
		parent::__construct();
	}
}
