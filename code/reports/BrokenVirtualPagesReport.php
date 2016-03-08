<?php

/**
 * @package cms
 * @subpackage reports
 */
class BrokenVirtualPagesReport extends SS_Report {

	public function title() {
		return _t('SideReport.BROKENVIRTUALPAGES', 'VirtualPages pointing to deleted pages');
	}

	public function group() {
		return _t('SideReport.BrokenLinksGroupTitle', "Broken links reports");
	}

	public function sourceRecords($params = null) {
		$classes = ClassInfo::subclassesFor('VirtualPage');
		$classParams = DB::placeholders($classes);
		$classFilter = array(
			"\"ClassName\" IN ($classParams) AND \"HasBrokenLink\" = 1" => $classes
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
class SideReport_BrokenVirtualPages extends BrokenVirtualPagesReport {
	public function __construct() {
		Deprecation::notice('4.0', 'Use BrokenVirtualPagesReport instead');
		parent::__construct();
	}
}
