<?php

/**
 * @package cms
 * @subpackage reports
 */
class RecentlyEditedReport extends SS_Report {

	public function title() {
		return _t('SideReport.LAST2WEEKS',"Pages edited in the last 2 weeks");
	}

	public function group() {
		return _t('SideReport.ContentGroupTitle', "Content reports");
	}

	public function sort() {
		return 200;
	}

	public function sourceRecords($params = null) {
		$threshold = strtotime('-14 days', SS_Datetime::now()->Format('U'));
		return DataObject::get("SiteTree", "\"SiteTree\".\"LastEdited\" > '".date("Y-m-d H:i:s", $threshold)."'", "\"SiteTree\".\"LastEdited\" DESC");
	}
	
	public function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
		);
	}
}

/**
 * @deprecated 3.2..4.0
 */
class SideReport_RecentlyEdited extends RecentlyEditedReport {
	public function __construct() {
		Deprecation::notice('4.0', 'Use RecentlyEditedReport instead');
		parent::__construct();
	}
}
