<?php

class SSReportTest extends SapphireTest {
	
	function testCanSortBy() {
		$report = new SSReportTest_FakeTest();
		$this->assertTrue($report->sourceQuery(array())->canSortBy('Title ASC'));
		$this->assertTrue($report->sourceQuery(array())->canSortBy('Title DESC'));
		$this->assertTrue($report->sourceQuery(array())->canSortBy('Title'));
	}
}

class SSReportTest_FakeTest extends SS_Report implements TestOnly {
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
		return new DataObjectSet();
	}
}