<?php

class ReportTest extends SapphireTest {

	/**
	 *	ASSERT whether a report is returning the correct results, based on a broken "draft" and/or "published" page, both with and without the "reason".
	 *
	 *	@parameter ss_report
	 *	@parameter boolean
	 *	@parameter boolean
	 *	@parameter string
	 */

	public function isReportBroken($report, $isDraftBroken, $isPublishedBroken, $reason) {

		$class = get_class($report);
		$parameters = array();

		// ASSERT that the "draft" report is returning the correct results, both with and without the "reason".

		$parameters['CheckSite'] = 'Draft';
		$results = (count($report->sourceRecords($parameters, null, null)) > 0) && (count($report->sourceRecords(array_merge($parameters, array('Reason' => $reason)), null, null)) > 0);
		$isDraftBroken ? $this->assertTrue($results, "{$class} has NOT returned the correct DRAFT results, as NO pages were found.") : $this->assertFalse($results, "{$class} has NOT returned the correct DRAFT results, as pages were found.");

		// ASSERT that the "published" report is returning the correct results, both with and without the "reason".

		$parameters['CheckSite'] = 'Published';
		$results = (count($report->sourceRecords($parameters, null, null)) > 0) && (count($report->sourceRecords(array_merge($parameters, array('Reason' => $reason)), null, null)) > 0);
		$isPublishedBroken ? $this->assertTrue($results, "{$class} has NOT returned the correct PUBLISHED results, as NO pages were found.") : $this->assertFalse($results, "{$class} has NOT returned the correct PUBLISHED results, as pages were found.");
	}

	public function testGetReports() {
		$reports = SS_Report::get_reports();
		$this->assertNotNull($reports, "Reports returned");
		$previousSort = 0;
		foreach($reports as $report) {
			$this->assertGreaterThanOrEqual($previousSort, $report->sort, "Reports are in correct sort order");
			$previousSort = $report->sort;
		}
	}

	public function testExcludeReport() {
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

	public function testAbstractClassesAreExcluded() {
		$reports = SS_Report::get_reports();
		$reportNames = array();
		foreach($reports as $report) {
			$reportNames[] = $report->class;
		}
		$this->assertNotContains('ReportTest_FakeTest_Abstract',
			$reportNames,
			'ReportTest_FakeTest_Abstract is NOT in reports list as it is abstract');
	}

	/**
	 *	Test the broken links report.
	 */

	public function testBrokenLinksReport() {

		// ---
		// BROKEN LINKS
		// ---

		// Create a "draft" page with a broken link.

		$page = Page::create();
		$page->Content = "<a href='[sitetree_link,id=987654321]'>This</a> is a broken link.";
		$page->writeToStage('Stage');

		// Retrieve the broken links report.

		$reports = SS_Report::get_reports();
		$brokenLinksReport = null;
		foreach($reports as $report) {
			if($report instanceof BrokenLinksReport) {
				$brokenLinksReport = $report;
				break;
			}
		}

		// Determine that the report exists, otherwise it has been excluded.

		if($brokenLinksReport) {

			// ASSERT that the "draft" report has detected the page having a broken link.
			// ASSERT that the "published" report has NOT detected the page having a broken link, as the page has not been "published" yet.

			$this->isReportBroken($brokenLinksReport, true, false, 'BROKENLINK');

			// Make sure the page is now "published".

			$page->writeToStage('Live');

			// ASSERT that the "draft" report has detected the page having a broken link.
			// ASSERT that the "published" report has detected the page having a broken link.

			$this->isReportBroken($brokenLinksReport, true, true, 'BROKENLINK');

			// Correct the "draft" broken link.

			$page->Content = str_replace('987654321', $page->ID, $page->Content);
			$page->writeToStage('Stage');

			// ASSERT that the "draft" report has NOT detected the page having a broken link.
			// ASSERT that the "published" report has detected the page having a broken link, as the previous content remains "published".

			$this->isReportBroken($brokenLinksReport, false, true, 'BROKENLINK');

			// Make sure the change has now been "published".

			$page->writeToStage('Live');

			// ASSERT that the "draft" report has NOT detected the page having a broken link.
			// ASSERT that the "published" report has NOT detected the page having a broken link.

			$this->isReportBroken($brokenLinksReport, false, false, 'BROKENLINK');
			$page->delete();

			// ---
			// BROKEN FILES
			// ---

			// Create a "draft" page with a broken file.

			$page = Page::create();
			$page->Content = "<a href='[file_link,id=987654321]'>This</a> is a broken file.";
			$page->writeToStage('Stage');

			// ASSERT that the "draft" report has detected the page having a broken file.
			// ASSERT that the "published" report has NOT detected the page having a broken file, as the page has not been "published" yet.

			$this->isReportBroken($brokenLinksReport, true, false, 'BROKENFILE');

			// Make sure the page is now "published".

			$page->writeToStage('Live');

			// ASSERT that the "draft" report has detected the page having a broken file.
			// ASSERT that the "published" report has detected the page having a broken file.

			$this->isReportBroken($brokenLinksReport, true, true, 'BROKENFILE');

			// Correct the "draft" broken file.

			$file = File::create();
			$file->Filename = 'name.pdf';
			$file->write();
			$page->Content = str_replace('987654321', $file->ID, $page->Content);
			$page->writeToStage('Stage');

			// ASSERT that the "draft" report has NOT detected the page having a broken file.
			// ASSERT that the "published" report has detected the page having a broken file, as the previous content remains "published".

			$this->isReportBroken($brokenLinksReport, false, true, 'BROKENFILE');

			// Make sure the change has now been "published".

			$page->writeToStage('Live');

			// ASSERT that the "draft" report has NOT detected the page having a broken file.
			// ASSERT that the "published" report has NOT detected the page having a broken file.

			$this->isReportBroken($brokenLinksReport, false, false, 'BROKENFILE');
			$page->delete();

			// ---
			// BROKEN VIRTUAL PAGES
			// ---

			// Create a "draft" virtual page with a broken link.

			$page = VirtualPage::create();
			$page->CopyContentFromID = 987654321;
			$page->writeToStage('Stage');

			// ASSERT that the "draft" report has detected the page having a broken link.
			// ASSERT that the "published" report has NOT detected the page having a broken link, as the page has not been "published" yet.

			$this->isReportBroken($brokenLinksReport, true, false, 'VPBROKENLINK');

			// Make sure the page is now "published".

			$page->writeToStage('Live');

			// ASSERT that the "draft" report has detected the page having a broken link.
			// ASSERT that the "published" report has detected the page having a broken link.

			$this->isReportBroken($brokenLinksReport, true, true, 'VPBROKENLINK');

			// Correct the "draft" broken link.

			$contentPage = Page::create();
			$contentPage->Content = 'This is some content.';
			$contentPage->writeToStage('Stage');
			$contentPage->writeToStage('Live');
			$page->CopyContentFromID = $contentPage->ID;
			$page->writeToStage('Stage');

			// ASSERT that the "draft" report has NOT detected the page having a broken link.
			// ASSERT that the "published" report has detected the page having a broken link, as the previous content remains "published".

			$this->isReportBroken($brokenLinksReport, false, true, 'VPBROKENLINK');

			// Make sure the change has now been "published".

			$page->writeToStage('Live');

			// ASSERT that the "draft" report has NOT detected the page having a broken link.
			// ASSERT that the "published" report has NOT detected the page having a broken link.

			$this->isReportBroken($brokenLinksReport, false, false, 'VPBROKENLINK');
			$contentPage->delete();
			$page->delete();

			// ---
			// BROKEN REDIRECTOR PAGES
			// ---

			// Create a "draft" redirector page with a broken link.

			$page = RedirectorPage::create();
			$page->RedirectionType = 'Internal';
			$page->LinkToID = 987654321;
			$page->writeToStage('Stage');

			// ASSERT that the "draft" report has detected the page having a broken link.
			// ASSERT that the "published" report has NOT detected the page having a broken link, as the page has not been "published" yet.

			$this->isReportBroken($brokenLinksReport, true, false, 'RPBROKENLINK');

			// Make sure the page is now "published".

			$page->writeToStage('Live');

			// ASSERT that the "draft" report has detected the page having a broken link.
			// ASSERT that the "published" report has detected the page having a broken link.

			$this->isReportBroken($brokenLinksReport, true, true, 'RPBROKENLINK');

			// Correct the "draft" broken link.

			$contentPage = Page::create();
			$contentPage->Content = 'This is some content.';
			$contentPage->writeToStage('Stage');
			$contentPage->writeToStage('Live');
			$page->LinkToID = $contentPage->ID;
			$page->writeToStage('Stage');

			// ASSERT that the "draft" report has NOT detected the page having a broken link.
			// ASSERT that the "published" report has detected the page having a broken link, as the previous content remains "published".

			$this->isReportBroken($brokenLinksReport, false, true, 'RPBROKENLINK');

			// Make sure the change has now been "published".

			$page->writeToStage('Live');

			// ASSERT that the "draft" report has NOT detected the page having a broken link.
			// ASSERT that the "published" report has NOT detected the page having a broken link.

			$this->isReportBroken($brokenLinksReport, false, false, 'RPBROKENLINK');
		}
	}

}

class ReportTest_FakeTest extends SS_Report implements TestOnly {
	public function title() {
		return 'Report title';
	}
	public function columns() {
		return array(
			"Title" => array(
				"title" => "Page Title"
			)
		);
	}
	public function sourceRecords($params, $sort, $limit) {
		return new ArrayList();
	}

	public function sort() {
		return 100;
	}
}


class ReportTest_FakeTest2 extends SS_Report implements TestOnly {
	public function title() {
		return 'Report title 2';
	}
	public function columns() {
		return array(
			"Title" => array(
				"title" => "Page Title 2"
			)
		);
	}
	public function sourceRecords($params, $sort, $limit) {
		return new ArrayList();
	}

	public function sort() {
		return 98;
	}
}

abstract class ReportTest_FakeTest_Abstract extends SS_Report implements TestOnly {
	public function title() {
		return 'Report title Abstract';
	}
	public function columns() {
		return array(
			"Title" => array(
				"title" => "Page Title Abstract"
			)
		);
	}
	public function sourceRecords($params, $sort, $limit) {
		return new ArrayList();
	}

	public function sort() {
		return 5;
	}
}

