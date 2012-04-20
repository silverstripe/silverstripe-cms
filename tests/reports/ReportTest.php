<?php

class ReportTest extends SapphireTest {

	function testGetReports() {
		$reports = SS_Report::get_reports();
		$this->assertNotNull($reports, "Reports returned");
		$previousSort = 0;
		foreach($reports as $report) {
			$this->assertGreaterThanOrEqual($previousSort, $report->sort, "Reports are in correct sort order");
			$previousSort = $report->sort;
		}
	}

	function testExcludeReport() {
		$reports = SS_Report::get_reports();
		$reportNames = array();
		foreach($reports as $report) {
			$reportNames[] = $report->class;
		}
		$this->assertContains('ReportTest_FakeTest',$reportNames,'ReportTest_FakeTest is in reports list');

		//exclude one report
		SS_Report::add_excluded_reports('ReportTest_FakeTest');

		$reports = SS_Report::get_reports();
		$reportNames = array();
		foreach($reports as $report) {
			$reportNames[] = $report->class;
		}
		$this->assertNotContains('ReportTest_FakeTest',$reportNames,'ReportTest_FakeTest is NOT in reports list');

		//exclude two reports
		SS_Report::add_excluded_reports(array('ReportTest_FakeTest','ReportTest_FakeTest2'));

		$reports = SS_Report::get_reports();
		$reportNames = array();
		foreach($reports as $report) {
			$reportNames[] = $report->class;
		}
		$this->assertNotContains('ReportTest_FakeTest',$reportNames,'ReportTest_FakeTest is NOT in reports list');
		$this->assertNotContains('ReportTest_FakeTest2',$reportNames,'ReportTest_FakeTest2 is NOT in reports list');
	}

	function testAbstractClassesAreExcluded() {
		$reports = SS_Report::get_reports();
		$reportNames = array();
		foreach($reports as $report) {
			$reportNames[] = $report->class;
		}
		$this->assertNotContains('ReportTest_FakeTest_Abstract',
			$reportNames,
			'ReportTest_FakeTest_Abstract is NOT in reports list as it is abstract');
	}
}

class ReportTest_FakeTest extends SS_Report implements TestOnly {
	function title() {
		return 'Report title';
	}
	function columns() {
		return array(
			"Title" => array(
				"title" => "Page Title"
			)
		);
	}
	function sourceRecords($params, $sort, $limit) {
		return new ArrayList();
	}

	function sort() {
		return 100;
	}
}


class ReportTest_FakeTest2 extends SS_Report implements TestOnly {
	function title() {
		return 'Report title 2';
	}
	function columns() {
		return array(
			"Title" => array(
				"title" => "Page Title 2"
			)
		);
	}
	function sourceRecords($params, $sort, $limit) {
		return new ArrayList();
	}

	function sort() {
		return 98;
	}
}

abstract class ReportTest_FakeTest_Abstract extends SS_Report implements TestOnly {
	function title() {
		return 'Report title Abstract';
	}
	function columns() {
		return array(
			"Title" => array(
				"title" => "Page Title Abstract"
			)
		);
	}
	function sourceRecords($params, $sort, $limit) {
		return new ArrayList();
	}

	function sort() {
		return 5;
	}
}

