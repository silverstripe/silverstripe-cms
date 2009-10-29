<?php
/**
 * Base class for the small reports that appear in the left hand site of the Site Content section of the CMS.
 * Create subclasses of this class to build new reports.
 * @package cms
 * @subpackage content
 */
abstract class SideReport extends Object {
	protected $params = array();
	
	abstract function records();
	abstract function fieldsToShow();
	abstract function title();
	
	function group() {
		return 'Other';
	}
	
	function sort() {
		return 0;
	}
	
	function getHTML() {
		$records = $this->records();
		$fieldsToShow = $this->fieldsToShow();
		
		if($records) {
			$result = "<ul class=\"$this->class\">\n";
			
			foreach($records as $record) {
				$result .= "<li>\n";
				foreach($fieldsToShow as $fieldTitle => $fieldInfo) {
					if(isset($fieldInfo['source'])) {
						$fieldSource = $fieldInfo['source'];
						
					// Legacy format for the input data
					} else {
						$fieldSource = $fieldInfo;
						$fieldInfo = array(
							'link' => true,
							'newline' => false,
						);
					}
					
					$val = isset($fieldInfo['prefix']) ? $fieldInfo['prefix'] : '';
					
					$fieldName = ereg_replace('[^A-Za-z0-9]+','',$fieldTitle);
					if(is_string($fieldSource)) {
						$val .= Convert::raw2xml($record->$fieldSource);
					} else {
						$val .= $record->val($fieldSource[0], $fieldSource[1]);
					}
					
					if(isset($fieldInfo['newline']) && $fieldInfo['newline']) $result .= "<br>";
					
					if(isset($fieldInfo['link']) && $fieldInfo['link']) {
						$link = ($fieldInfo['link'] === true) ? "admin/show/$record->ID" : $fieldInfo['link'];
						$result .= "<a class=\"$fieldName\" href=\"$link\">$val</a>";
					} else {
						$result .= "<span class=\"$fieldName\">$val</span>";
					}
					
					$val .= isset($fieldInfo['suffix']) ? $fieldInfo['suffix'] : '';
				}
				$result .= "\n</li>\n";
			}
			$result .= "</ul>\n";	
		} else {
			$result = "<p class=\"message notice\">" . 
				sprintf(
					_t('SideReport.REPEMPTY','The %s report is empty.',PR_MEDIUM,'%s is a report title'),
					$this->title()
				) 
				. "</p>";
		}
		return $result;
	}
	
	function setParams($params) {
		$this->params = $params;
	}
	
	// if your batchaction has parameters, return a fieldset here
	function getParameterFields() {
		return false;
	}
	
	function canView() {
		return true;
	}
}

/**
 * Content side-report listing empty pages
 * @package cms
 * @subpackage content
 */
class SideReport_EmptyPages extends SideReport {
	function title() {
		return _t('SideReport.EMPTYPAGES',"Pages with no content");
	}
	function group() {
		return "Content reports";
	}
	function sort() {
		return 100;
	}
	function records($params = null) {
		return DataObject::get("SiteTree", "\"Content\" = '' OR \"Content\" IS NULL OR \"Content\" LIKE '<p></p>' OR \"Content\" LIKE '<p>&nbsp;</p>'", '"Title"');
	}
	function fieldsToShow() {
		return array(
			"Title" => array("NestedTitle", array("2")),
		);
	}
}

/**
 * Content side-report listing recently editing pages.
 * @package cms
 * @subpackage content
 */
class SideReport_RecentlyEdited extends SideReport {
	function title() {
		return _t('SideReport.LAST2WEEKS',"Pages edited in the last 2 weeks");
	}
	function group() {
		return "Content reports";
	}
	function sort() {
		return 200;
	}
	function records($params = null) {
		$threshold = strtotime('-14 days', SS_Datetime::now()->Format('U'));
		return DataObject::get("SiteTree", "\"SiteTree\".\"LastEdited\" > '".date("Y-m-d H:i:s", $threshold)."'", "\"SiteTree\".\"LastEdited\" DESC");
	}
	function fieldsToShow() {
		return array(
			"Title" => array("NestedTitle", array("2")),
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
 * Lists all pages with either broken page or file links.
 *
 * @package cms
 * @subpackage content
 */
class SideReport_BrokenLinks extends SideReport {
	
	public function title() {
		return _t('SideReport.BROKENPAGEFILELINKS', 'Broken Page & File Links');
	}
	
	function group() {
		return "Broken links reports";
	}
	
	public function records() {
		return DataObject::get('SiteTree', '"HasBrokenLink" = 1 OR "HasBrokenFile" = 1');
	}
	
	public function fieldsToShow() {
		return array(
			'Title' => array('NestedTitle', array(2)),
		);
	}
	
}