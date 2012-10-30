<?php

/**
 * Renderer for showing SideReports in CMSMain
 * 
 * 
 * @package cms
 * @subpackage content
 * 
 * @package cms
 * @subpackage content
 */
class SideReportView extends ViewableData {
	protected $controller, $report;
	protected $parameters;
	
	public function __construct($controller, $report) {
		$this->controller = $controller;
		$this->report = $report;
		parent::__construct();
	}
	
	public function group() {
		return _t('SideReport.OtherGroupTitle', "Other");
	}
	
	public function sort() {
		return 0;
	}
	
	public function setParameters($parameters) {
		$this->parameters = $parameters;
	}
	
	public function forTemplate() {
		$records = $this->report->records($this->parameters);
		$columns = $this->report->columns();
		
		if($records && $records->Count()) {
			$result = "<ul class=\"$this->class\">\n";
			
			foreach($records as $record) {
				$result .= "<li>\n";
				foreach($columns as $source => $info) {
					if(is_string($info)) $info = array('title' => $info);
					$result .= $this->formatValue($record, $source, $info);
				}
				$result .= "\n</li>\n";
			}
			$result .= "</ul>\n";	
		} else {
			$result = "<p class=\"message notice\">" . 
				_t(
					'SideReport.REPEMPTY',
					'The {title} report is empty.',
					array('title' => $this->report->title())
				) 
				. "</p>";
		}
		return $result;
	}
	
	protected function formatValue($record, $source, $info) {
		// Field sources
		//if(is_string($source)) {
			$val = Convert::raw2xml($record->$source);
		//} else {
		//	$val = $record->val($source[0], $source[1]);
		//}
		
		// Casting, a la TableListField.  We're deep-calling a helper method on TableListField that
		// should probably be pushed elsewhere...
		if(!empty($info['casting'])) {
			$val = TableListField::getCastedValue($val, $info['casting']);
		}
		
		// Formatting, a la TableListField
		if(!empty($info['formatting'])) {
			$format = str_replace('$value', "__VAL__", $info['formatting']);
			$format = preg_replace('/\$([A-Za-z0-9-_]+)/','$record->$1', $format);
			$format = str_replace('__VAL__', '$val', $format);
			$val = eval('return "' . $format . '";');
		}

		$prefix = empty($info['newline']) ? "" : "<br>";

		
		$classClause = "";
		if(isset($info['title'])) {
			$cssClass = preg_replace('/[^A-Za-z0-9]+/', '', $info['title']);
			$classClause = "class=\"$cssClass\"";
		}
		
		if(isset($info['link']) && $info['link']) {
			$linkBase = singleton('CMSPageEditController')->Link('show') . '/';
			$link = ($info['link'] === true) ? $linkBase . $record->ID : $info['link'];
			return $prefix . "<a $classClause href=\"$link\">$val</a>";
		} else {
			return $prefix . "<span $classClause>$val</span>";
		}

	}
}

/**
 * A report wrapper that makes it easier to define slightly different behaviour for side-reports.
 * 
 * This report wrapper will use sideReportColumns() for the report columns, instead of columns().
 * 
 * @package cms
 * @subpackage content
 */
class SideReportWrapper extends SS_ReportWrapper {
	public function columns() {
		if($this->baseReport->hasMethod('sideReportColumns')) {
			return $this->baseReport->sideReportColumns();
		} else {
			return parent::columns();
		}
	}
}

////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Content side-report listing empty pages
 * 
 * @package cms
 * @subpackage content
 */
class SideReport_EmptyPages extends SS_Report {
	public function title() {
		return _t('SideReport.EMPTYPAGES',"Pages with no content");
	}

	public function group() {
		return _t('SideReport.ContentGroupTitle', "Content reports");
	}
	public function sort() {
		return 100;
	}
	public function sourceRecords($params = null) {
		return DataObject::get("SiteTree", "\"ClassName\" != 'RedirectorPage' AND (\"Content\" = '' OR \"Content\" IS NULL OR \"Content\" LIKE '<p></p>' OR \"Content\" LIKE '<p>&nbsp;</p>')", '"Title"');
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
 * Content side-report listing recently editing pages.
 * 
 * @package cms
 * @subpackage content
 */
class SideReport_RecentlyEdited extends SS_Report {
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
 * Content side-report listing pages with broken links
 * 
 * @package cms
 * @subpackage content
 */
class SideReport_BrokenLinks extends SS_Report {
	public function title() {
		return _t('SideReport.BROKENLINKS',"Pages with broken links");
	}
	public function group() {
		return _t('SideReport.BrokenLinksGroupTitle', "Broken links reports");
	}
	public function sourceRecords($params = null) {
		// Get class names for page types that are not virtual pages or redirector pages
		$classes = array_diff(ClassInfo::subclassesFor('SiteTree'), ClassInfo::subclassesFor('VirtualPage'), ClassInfo::subclassesFor('RedirectorPage'));
		$classNames = "'".join("','", $classes)."'";
		
		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "\"ClassName\" IN ($classNames) AND \"HasBrokenLink\" = 1");
		else $ret = DataObject::get('SiteTree', "\"ClassName\" IN ($classNames) AND \"HasBrokenLink\" = 1");
		return $ret;
	}
	public function columns() {
		return array(
			"Title" => array(
				"title" => _t('ReportAdmin.ReportTitle', 'Title'), // todo: use NestedTitle(2)
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
 * Content side-report listing pages with broken files
 * or asset links
 * 
 * @package cms
 * @subpackage content
 */
class SideReport_BrokenFiles extends SS_Report {
	public function title() {
		return _t('SideReport.BROKENFILES',"Pages with broken files");
	}
	public function group() {
		return _t('SideReport.BrokenLinksGroupTitle', "Broken links reports");
	}
	public function sourceRecords($params = null) {
		// Get class names for page types that are not virtual pages or redirector pages
		$classes = array_diff(ClassInfo::subclassesFor('SiteTree'), ClassInfo::subclassesFor('VirtualPage'), ClassInfo::subclassesFor('RedirectorPage'));
		$classNames = "'".join("','", $classes)."'";
		
		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "\"ClassName\" IN ($classNames) AND \"HasBrokenFile\" = 1");
		else $ret = DataObject::get('SiteTree', "\"ClassName\" IN ($classNames) AND \"HasBrokenFile\" = 1");
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

/**
 * @package cms
 * @subpackage content
 */
class SideReport_BrokenVirtualPages extends SS_Report {
	public function title() {
		return _t('SideReport.BROKENVIRTUALPAGES', 'VirtualPages pointing to deleted pages');
	}
	public function group() {
		return _t('SideReport.BrokenLinksGroupTitle', "Broken links reports");
	}
	public function sourceRecords($params = null) {
		$classNames = "'".join("','", ClassInfo::subclassesFor('VirtualPage'))."'";
		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "\"ClassName\" IN ($classNames) AND \"HasBrokenLink\" = 1");
		else $ret = DataObject::get('SiteTree', "\"ClassName\" IN ($classNames) AND \"HasBrokenLink\" = 1");
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

/**
 * @package cms
 * @subpackage content
 */
class SideReport_BrokenRedirectorPages extends SS_Report {
	public function title() {
		return _t('SideReport.BROKENREDIRECTORPAGES', 'RedirectorPages pointing to deleted pages');
	}
	public function group() {
		return _t('SideReport.BrokenLinksGroupTitle', "Broken links reports");
	}
	public function sourceRecords($params = null) {
		$classNames = "'".join("','", ClassInfo::subclassesFor('RedirectorPage'))."'";
		
		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "\"ClassName\" IN ($classNames) AND \"HasBrokenLink\" = 1");
		else $ret = DataObject::get('SiteTree', "\"ClassName\" IN ($classNames) AND \"HasBrokenLink\" = 1");
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
