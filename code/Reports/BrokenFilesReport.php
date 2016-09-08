<?php

namespace SilverStripe\CMS\Reports;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Reports\Report;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Versioning\Versioned;

class BrokenFilesReport extends Report {

	public function title() {
		return _t('SideReport.BROKENFILES',"Pages with broken files");
	}

	public function group() {
		return _t('SideReport.BrokenLinksGroupTitle', "Broken links reports");
	}

	public function sourceRecords($params = null) {
		// Get class names for page types that are not virtual pages or redirector pages
		$classes = array_diff(
			ClassInfo::subclassesFor('SilverStripe\\CMS\\Model\\SiteTree'),
			ClassInfo::subclassesFor('SilverStripe\\CMS\\Model\\VirtualPage'),
			ClassInfo::subclassesFor('SilverStripe\\CMS\\Model\\RedirectorPage')
		);
		$classParams = DB::placeholders($classes);
		$classFilter = array(
			"\"ClassName\" IN ($classParams) AND \"HasBrokenFile\" = 1" => $classes
		);

		$stage = isset($params['OnLive']) ? 'Live' : 'Stage';
		return Versioned::get_by_stage('SilverStripe\\CMS\\Model\\SiteTree', $stage, $classFilter);
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

