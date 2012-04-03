<?php

class ReportTest extends SapphireTest {
	
	function testCanSortBy() {
		$report = new SSReportTest_FakeTest();
		$this->assertTrue($report->sourceQuery(array())->canSortBy('Title ASC'));
		$this->assertTrue($report->sourceQuery(array())->canSortBy('Title DESC'));
		$this->assertTrue($report->sourceQuery(array())->canSortBy('Title'));
	}

	function testGetReports() {
		$reports = SS_Report::get_reports();
		$this->assertNotNull($reports, "Reports returned");
		Debug::Show($reports);
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
		return 98;
	}
}