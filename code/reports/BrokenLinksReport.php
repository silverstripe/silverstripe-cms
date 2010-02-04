<?php

/**
 * Content side-report listing pages with broken links
 * @package cms
 * @subpackage content
 */

class BrokenLinksReport extends SSReport {
	function title() {
		return _t('BrokenLinksReport.BROKENLINKS',"Pages with broken links");
	}
	function sourceRecords($params = null) {
		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "(HasBrokenLink = 1 OR HasBrokenFile = 1)");
		else $ret = DataObject::get('SiteTree', "(HasBrokenFile = 1 OR HasBrokenLink = 1)");
		
		$returnSet = new DataObjectSet();
		if ($ret) foreach($ret as $record) {
			$reason = false;
			$isRedirectorPage = in_array($record->ClassName, ClassInfo::subclassesFor('RedirectorPage'));
			$isVirtualPage = in_array($record->ClassName, ClassInfo::subclassesFor('VirtualPage'));
			
			if ($isVirtualPage) {
				if ($record->HasBrokenLink) {
					$reason = "virtual page pointing to non-existant page";
					$reasonCodes = array("VPBROKENLINK");
				}
			} else if ($isRedirectorPage) {
				if ($record->HasBrokenLink) {
					$reason = "redirector page pointing to non-existant page";
					$reasonCodes = array("RPBROKENLINK");
				}
			} else {
				if ($record->HasBrokenLink && $record->HasBrokenFile) {
					$reason = "has broken link and file";
					$reasonCodes = array("BROKENFILE", "BROKENLINK");
				} else if ($record->HasBrokenLink && !$record->HasBrokenFile) {
					$reason = "has broken link";
					$reasonCodes = array("BROKENLINK");
				} else if (!$record->HasBrokenLink && $record->HasBrokenFile) {
					$reason = "has broken file";
					$reasonCodes = array("BROKENFILE");
				}
			}
			
			if ($reason) {
				if ($params['Reason'] && !in_array($params['Reason'], $reasonCodes)) continue;
				$record->BrokenReason = $reason;
				$returnSet->push($record);
			}
		}
		
		return $returnSet;
	}
	function columns() {
		$fields = array(
			"Title" => array(
				"title" => "Title",
				'formatting' => '<a href=\"admin/show/$ID\" title=\"Edit page\">$value</a>'
			),
			"BrokenReason" => array(
				"title" => "Reason"
			),
			'AbsoluteLink' => array(
				'title' => 'Links',
				'formatting' => '$value " . ($AbsoluteLiveLink ? "<a href=\"$AbsoluteLiveLink\">(live)</a>" : "") . " <a href=\"$value?stage=Stage\">(draft)</a>'
			)
		);
		
		return $fields;
	}
	function parameterFields() {
		return new FieldSet(
			new CheckboxField('OnLive', 'Check live site'),
			new DropdownField('Reason', 'Problem to check', array(
				'' => 'Any',
				'BROKENFILE' => 'Broken file',
				'BROKENLINK' => 'Broken link',
				'VPBROKENLINK' => 'Virtual page pointing to invalid source',
				'RPBROKENLINK' => 'Redirector page pointing to invalid destination',
			))
		);
	}
}
