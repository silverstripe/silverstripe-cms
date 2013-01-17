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
		$classNames = "'".join("','", ClassInfo::subclassesFor('VirtualPage'))."'";
		
		if (isset($_REQUEST['OnLive'])) {
			$ret = Versioned::get_by_stage('SiteTree', 'Live', "\"ClassName\" IN ($classNames) AND \"HasBrokenLink\" = 1");
		} else {
			$ret = DataObject::get('SiteTree', "\"ClassName\" IN ($classNames) AND \"HasBrokenLink\" = 1");
		}
		
		return $ret;
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