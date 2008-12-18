<?php
/**
 * Base class for the small reports that appear in the left hand site of the Site Content section of the CMS.
 * Create subclasses of this class to build new reports.
 * @package cms
 * @subpackage content
 */
abstract class SideReport extends Object {
	abstract function records();
	abstract function fieldsToShow();
	abstract function title();
	
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
					
					$fieldName = ereg_replace('[^A-Za-z0-9]+','',$fieldTitle);
					if(is_string($fieldSource)) {
						$val = Convert::raw2xml($record->$fieldSource);
					} else {
						$val = $record->val($fieldSource[0], $fieldSource[1]);
					}
					
					if(isset($fieldInfo['newline']) && $fieldInfo['newline']) $result .= "<br>";
					
					if(isset($fieldInfo['link']) && $fieldInfo['link']) {
						$link = ($fieldInfo['link'] === true) ? "admin/show/$record->ID" : $fieldInfo['link'];
						$result .= "<a class=\"$fieldName\" href=\"$link\">$val</a>";
					} else {
						$result .= "<span class=\"$fieldName\">$val</span>";
					}
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
}

/**
 * Content side-report listing empty pages
 * @package cms
 * @subpackage content
 */
class SideReport_EmptyPages extends SideReport {
	function title() {
		return _t('SideReport.EMPTYPAGES',"Empty pages");
	}
	function records() {
		return DataObject::get("SiteTree", "Content = '' OR Content IS NULL OR Content LIKE '<p></p>' OR Content LIKE '<p>&nbsp;</p>'", "Title");
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
	function records() {
		return DataObject::get("SiteTree", "`SiteTree`.LastEdited > NOW() - INTERVAL 14 DAY", "`SiteTree`.`LastEdited` DESC");
	}
	function fieldsToShow() {
		return array(
			"Title" => array("NestedTitle", array("2")),
		);
	}
}

class SideReport_ToDo extends SideReport {
	function title() {
		return _t('SideReport.TODO',"To do");
	}
	function records() {
		return DataObject::get("SiteTree", "`SiteTree`.ToDo IS NOT NULL AND `SiteTree`.ToDo <> ''", "`SiteTree`.`LastEdited` DESC");
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
?>