<?php

/**
 * @package cms
 * @subpackage
 */

/**
 * Base class for the small reports that appear in the left hand site of the Site Content section of the CMS.
 * Create subclasses of this class to build new reports.
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
				foreach($fieldsToShow as $fieldTitle => $fieldSource) {
					$fieldName = ereg_replace('[^A-Za-z0-9]+','',$fieldTitle);
					if(is_string($fieldSource)) {
						$val = $record->$fieldSource;
					} else {
						$val = $record->val($fieldSource[0], $fieldSource[1]);
					}
					
					$result .= "<a class=\"$fieldName\" href=\"admin/show/$record->ID\">$val</a>";
				}
				$result .= "\n</li>\n";
			}
			$result .= "</ul>\n";	
		} else {
			$result = sprintf(_t('SideReport.REPEMPTY','The %s report is empty.',PR_MEDIUM,'%s is a report title'),$this->title());
		}
		return $result;
	}
}

/**
 * Content side-report listing empty pages
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
?>