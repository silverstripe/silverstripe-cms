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

		//excluse one report
		SS_Report::excludeReport('ReportTest_FakeTest');

		$reports = SS_Report::get_reports();
		$reportNames = array();
		foreach($reports as $report) {
			$reportNames[] = $report->class;
		}
		$this->assertNotContains('ReportTest_FakeTest',$reportNames,'ReportTest_FakeTest is NOT in reports list');
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