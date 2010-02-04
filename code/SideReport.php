<?php

/**
 * Renderer for showing SideReports in CMSMain
 */
class SideReportView extends ViewableData {
	protected $controller, $report;
	protected $parameters;
	
	function __construct($controller, $report) {
		$this->controller = $controller;
		$this->report = $report;
		parent::__construct();
	}
	
	function group() {
		return 'Other';
	}
	
	function sort() {
		return 0;
	}
	
	function setParameters($parameters) {
		$this->parameters = $parameters;
	}
	
	function forTemplate() {
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
				sprintf(
					_t('SideReport.REPEMPTY','The %s report is empty.',PR_MEDIUM,'%s is a report title'),
					$this->report->title()
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
		
		// Formatting, a la TableListField
		if(!empty($info['formatting'])) {
			$format = str_replace('$value', "__VAL__", $info['formatting']);
			$format = preg_replace('/\$([A-Za-z0-9-_]+)/','$record->$1', $format);
			$format = str_replace('__VAL__', '$val', $format);
			$val = eval('return "' . $format . '";');
		}

		$prefix = empty($info['newline']) ? "" : "<br>";

		
		$cssClass = ereg_replace('[^A-Za-z0-9]+','',$info['title']);
		if(isset($info['link']) && $info['link']) {
			$link = ($info['link'] === true) ? "admin/show/$record->ID" : $info['link'];
			return $prefix . "<a class=\"$cssClass\" href=\"$link\">$val</a>";
		} else {
			return $prefix . "<span class=\"$cssClass\">$val</span>";
		}

	}
}

////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Content side-report listing empty pages
 * @package cms
 * @subpackage content
 */
class SideReport_EmptyPages extends SS_Report {
	function title() {
		return _t('SideReport.EMPTYPAGES',"Pages with no content");
	}

	function group() {
		return "Content reports";
	}
	function sort() {
		return 100;
	}
	function sourceRecords($params = null) {
		return DataObject::get("SiteTree", "\"Content\" = '' OR \"Content\" IS NULL OR \"Content\" LIKE '<p></p>' OR \"Content\" LIKE '<p>&nbsp;</p>'", '"Title"');
	}
	function columns() {
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
 * @package cms
 * @subpackage content
 */
class SideReport_RecentlyEdited extends SS_Report {
	function title() {
		return _t('SideReport.LAST2WEEKS',"Pages edited in the last 2 weeks");
	}
	function group() {
		return "Content reports";
	}
	function sort() {
		return 200;
	}
	function sourceRecords($params = null) {
		$threshold = strtotime('-14 days', SS_Datetime::now()->Format('U'));
		return DataObject::get("SiteTree", "\"SiteTree\".\"LastEdited\" > '".date("Y-m-d H:i:s", $threshold)."'", "\"SiteTree\".\"LastEdited\" DESC");
	}
	function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
		);
	}
}

class SideReport_ToDo extends SideReport {
	function title() {
		return _t('SideReport.TODO',"Pages with To Do items");
	}
	function group() {
		return "Content reports";
	}
	function sort() {
		return 0;
	}
	function records($params = null) {
		return DataObject::get("SiteTree", "\"SiteTree\".\"ToDo\" IS NOT NULL AND \"SiteTree\".\"ToDo\" <> ''", "\"SiteTree\".\"LastEdited\" DESC");
	}
	function fieldsToShow() {
		return array(
			"Title" => array(
				"source" => array("NestedTitle", array("2")),
				"link" => true,
			),
			"ToDo" => array(
				"source" => "ToDo",
				"newline" => true,
			), 
		);
	}
}

/**
 * Content side-report listing pages with broken links
 * @package cms
 * @subpackage content
 */
class SideReport_BrokenLinks extends SS_Report {
	function title() {
		return _t('SideReport.BROKENLINKS',"Pages with broken links");
	}
	function group() {
		return "Broken links reports";
	}
	function sourceRecords($params = null) {
		// Get class names for page types that are not virtual pages or redirector pages
		$classes = array_diff(ClassInfo::subclassesFor('SiteTree'), ClassInfo::subclassesFor('VirtualPage'), ClassInfo::subclassesFor('RedirectorPage'));
		$classNames = "'".join("','", $classes)."'";
		
		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "ClassName IN ($classNames) AND HasBrokenLink = 1");
		else $ret = DataObject::get('SiteTree', "ClassName IN ($classNames) AND HasBrokenLink = 1");
		return $ret;
	}
	function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
		);
	}
	function parameterFields() {
		return new FieldSet(
			new CheckboxField('OnLive', 'Check live site')
		);
	}
}

/**
 * Content side-report listing pages with broken files
 * or asset links
 * @package cms
 * @subpackage content
 */
class SideReport_BrokenFiles extends SS_Report {
	function title() {
		return _t('SideReport.BROKENFILES',"Pages with broken files");
	}
	function group() {
		return "Broken links reports";
	}
	function sourceRecords($params = null) {
		// Get class names for page types that are not virtual pages or redirector pages
		$classes = array_diff(ClassInfo::subclassesFor('SiteTree'), ClassInfo::subclassesFor('VirtualPage'), ClassInfo::subclassesFor('RedirectorPage'));
		$classNames = "'".join("','", $classes)."'";
		
		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "ClassName IN ($classNames) AND HasBrokenFile = 1");
		else $ret = DataObject::get('SiteTree', "ClassName IN ($classNames) AND HasBrokenFile = 1");
		return $ret;
	}
	function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
		);
	}
	function parameterFields() {
		return new FieldSet(
			new CheckboxField('OnLive', 'Check live site')
		);
	}
}

class SideReport_BrokenVirtualPages extends SS_Report {
	function title() {
		return _t('SideReport.BROKENVIRTUALPAGES', 'VirtualPages pointing to deleted pages');
	}
	function group() {
		return "Broken links reports";
	}
	function sourceRecords($params = null) {
		$classNames = "'".join("','", ClassInfo::subclassesFor('VirtualPage'))."'";
		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "ClassName IN ($classNames) AND HasBrokenLink = 1");
		else $ret = DataObject::get('SiteTree', "ClassName IN ($classNames) AND HasBrokenLink = 1");
		return $ret;
	}
	
	function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
		);
	}
	function parameterFields() {
		return new FieldSet(
			new CheckboxField('OnLive', 'Check live site')
		);
	}
}

class SideReport_BrokenRedirectorPages extends SS_Report {
	function title() {
		return _t('SideReport.BROKENREDIRECTORPAGES', 'RedirectorPages pointing to deleted pages');
	}
	function group() {
		return "Broken links reports";
	}
	function sourceRecords($params = null) {
		$classNames = "'".join("','", ClassInfo::subclassesFor('RedirectorPage'))."'";
		
		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "ClassName IN ($classNames) AND HasBrokenLink = 1");
		else $ret = DataObject::get('SiteTree', "ClassName IN ($classNames) AND HasBrokenLink = 1");
		return $ret;
	}
	
	function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
		);
	}
	function parameterFields() {
		return new FieldSet(
			new CheckboxField('OnLive', 'Check live site')
		);
	}
}

class SideReport_ToDo extends SS_Report {
	function title() {
		return _t('SideReport.TODO',"To do");
	}
	function sourceRecords($params = null) {
		return DataObject::get("SiteTree", "\"SiteTree\".\"ToDo\" IS NOT NULL AND \"SiteTree\".\"ToDo\" <> ''", "\"SiteTree\".\"LastEdited\" DESC");
	}
	function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
			"ToDo" => array(
				"title" => "ToDo",
				"newline" => true,
			), 
		);
	}
}
