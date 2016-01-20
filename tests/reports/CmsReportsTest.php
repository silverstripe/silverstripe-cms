<?php
/**
 * @package cms
 * @subpackage tests
 */

class CmsReportsTest extends SapphireTest {

	protected static $fixture_file = 'CmsReportsTest.yml';

	private static $daysAgo = 14;
	
	public function setUp() {
		parent::setUp();

		// set the dates by hand: impossible to set via yml
		$afterThreshold = strtotime('-'.(self::$daysAgo-1).' days', strtotime('31-06-2009 00:00:00'));
		$beforeThreshold = strtotime('-'.(self::$daysAgo+1).' days', strtotime('31-06-2009 00:00:00'));

		$after = $this->objFromFixture('SiteTree', 'after');
		$before = $this->objFromFixture('SiteTree', 'before');
		
		DB::query("UPDATE \"SiteTree\" SET \"Created\"='2009-01-01 00:00:00', \"LastEdited\"='".date('Y-m-d H:i:s', $afterThreshold)."' WHERE \"ID\"='".$after->ID."'");
		DB::query("UPDATE \"SiteTree\" SET \"Created\"='2009-01-01 00:00:00', \"LastEdited\"='".date('Y-m-d H:i:s', $beforeThreshold)."' WHERE \"ID\"='".$before->ID."'");
	}

	/**
	 *	ASSERT whether a report is returning the correct results, based on a broken "draft" and/or "published" page.
	 *
	 *	@parameter ss_report
	 *	@parameter boolean
	 *	@parameter boolean
	 */

	public function isReportBroken($report, $isDraftBroken, $isPublishedBroken) {

		$class = get_class($report);

		// ASSERT that the "draft" report is returning the correct results.
		$parameters = array('CheckSite' => 'Draft');
		$results = count($report->sourceRecords($parameters, null, null)) > 0;
		$isDraftBroken ? $this->assertTrue($results, "{$class} has NOT returned the correct DRAFT results, as NO pages were found.") : $this->assertFalse($results, "{$class} has NOT returned the correct DRAFT results, as pages were found.");

		// ASSERT that the "published" report is returning the correct results.
		$parameters = array('CheckSite' => 'Published', 'OnLive' => 1);
		$results = count($report->sourceRecords($parameters, null, null)) > 0;
		$isPublishedBroken ? $this->assertTrue($results, "{$class} has NOT returned the correct PUBLISHED results, as NO pages were found.") : $this->assertFalse($results, "{$class} has NOT returned the correct PUBLISHED results, as pages were found.");
	}

	public function testRecentlyEdited() {
		SS_Datetime::set_mock_now('31-06-2009 00:00:00');

		$after = $this->objFromFixture('SiteTree', 'after');
		$before = $this->objFromFixture('SiteTree', 'before');

		$r = new RecentlyEditedReport();
		
		// check if contains only elements not older than $daysAgo days
		$this->assertNotNull($r->records(array()));
		$this->assertContains($after->ID, $r->records(array())->column('ID'));
		$this->assertNotContains($before->ID, $r->records(array())->column('ID'));
		
		SS_DateTime::clear_mock_now();
	}

	/**
	 *	Test the broken links side report.
	 */

	public function testBrokenLinks() {

		// Create a "draft" page with a broken link.

		$page = Page::create();
		$page->Content = "<a href='[sitetree_link,id=987654321]'>This</a> is a broken link.";
		$page->writeToStage('Stage');

		// Retrieve the broken links side report.

		$reports = SS_Report::get_reports();
		$brokenLinksReport = null;
		foreach($reports as $report) {
			if($report instanceof BrokenLinksReport) {
				$brokenLinksReport = $report;
				break;
			}
		}

		// Determine that the report exists, otherwise it has been excluded.
		if(!$brokenLinksReport){
			$this->markTestSkipped('BrokenLinksReport is not an available report');
			return;
		}

		// ASSERT that the "draft" report has detected the page having a broken link.
		// ASSERT that the "published" report has NOT detected the page having a broken link, as the page has not been "published" yet.

		$this->isReportBroken($brokenLinksReport, true, false);

		// Make sure the page is now "published".

		$page->writeToStage('Live');

		// ASSERT that the "draft" report has detected the page having a broken link.
		// ASSERT that the "published" report has detected the page having a broken link.

		$this->isReportBroken($brokenLinksReport, true, true);

		// Correct the "draft" broken link.

		$page->Content = str_replace('987654321', $page->ID, $page->Content);
		$page->writeToStage('Stage');

		// ASSERT that the "draft" report has NOT detected the page having a broken link.
		// ASSERT that the "published" report has detected the page having a broken link, as the previous content remains "published".

		$this->isReportBroken($brokenLinksReport, false, true);

		// Make sure the change has now been "published".

		$page->writeToStage('Live');

		// ASSERT that the "draft" report has NOT detected the page having a broken link.
		// ASSERT that the "published" report has NOT detected the page having a broken link.

		$this->isReportBroken($brokenLinksReport, false, false);
	}

	/**
	 *	Test the broken files side report.
	 */

	public function testBrokenFiles() {

		// Create a "draft" page with a broken file.

		$page = Page::create();
		$page->Content = "<a href='[file_link,id=987654321]'>This</a> is a broken file.";
		$page->writeToStage('Stage');

		// Retrieve the broken files side report.

		$reports = SS_Report::get_reports();
		$brokenFilesReport = null;
		foreach($reports as $report) {
			if($report instanceof BrokenFilesReport) {
				$brokenFilesReport = $report;
				break;
			}
		}

		// Determine that the report exists, otherwise it has been excluded.
		if(!$brokenFilesReport){
			$this->markTestSkipped('BrokenFilesReport is not an available report');
			return;
		}

		// ASSERT that the "draft" report has detected the page having a broken file.
		// ASSERT that the "published" report has NOT detected the page having a broken file, as the page has not been "published" yet.

		$this->isReportBroken($brokenFilesReport, true, false);

		// Make sure the page is now "published".

		$page->writeToStage('Live');

		// ASSERT that the "draft" report has detected the page having a broken file.
		// ASSERT that the "published" report has detected the page having a broken file.

		$this->isReportBroken($brokenFilesReport, true, true);

		// Correct the "draft" broken file.

		$file = File::create();
		$file->Filename = 'name.pdf';
		$file->write();
		$page->Content = str_replace('987654321', $file->ID, $page->Content);
		$page->writeToStage('Stage');

		// ASSERT that the "draft" report has NOT detected the page having a broken file.
		// ASSERT that the "published" report has detected the page having a broken file, as the previous content remains "published".

		$this->isReportBroken($brokenFilesReport, false, true);

		// Make sure the change has now been "published".

		$page->writeToStage('Live');

		// ASSERT that the "draft" report has NOT detected the page having a broken file.
		// ASSERT that the "published" report has NOT detected the page having a broken file.

		$this->isReportBroken($brokenFilesReport, false, false);
	}

	/**
	 *	Test the broken virtual pages side report.
	 */

	public function testBrokenVirtualPages() {

		// Create a "draft" virtual page with a broken link.

		$page = VirtualPage::create();
		$page->CopyContentFromID = 987654321;
		$page->writeToStage('Stage');

		// Retrieve the broken virtual pages side report.

		$reports = SS_Report::get_reports();
		$brokenVirtualPagesReport = null;
		foreach($reports as $report) {
			if($report instanceof BrokenVirtualPagesReport) {
				$brokenVirtualPagesReport = $report;
				break;
			}
		}

		// Determine that the report exists, otherwise it has been excluded.
		if(!$brokenVirtualPagesReport){
			$this->markTestSkipped('BrokenFilesReport is not an available report');
			return;
		}

		// ASSERT that the "draft" report has detected the page having a broken link.
		// ASSERT that the "published" report has NOT detected the page having a broken link, as the page has not been "published" yet.

		$this->isReportBroken($brokenVirtualPagesReport, true, false);

		// Make sure the page is now "published".

		$page->writeToStage('Live');

		// ASSERT that the "draft" report has detected the page having a broken link.
		// ASSERT that the "published" report has detected the page having a broken link.

		$this->isReportBroken($brokenVirtualPagesReport, true, true);

		// Correct the "draft" broken link.

		$contentPage = Page::create();
		$contentPage->Content = 'This is some content.';
		$contentPage->writeToStage('Stage');
		$contentPage->writeToStage('Live');
		$page->CopyContentFromID = $contentPage->ID;
		$page->writeToStage('Stage');

		// ASSERT that the "draft" report has NOT detected the page having a broken link.
		// ASSERT that the "published" report has detected the page having a broken link, as the previous content remains "published".

		$this->isReportBroken($brokenVirtualPagesReport, false, true);

		// Make sure the change has now been "published".

		$page->writeToStage('Live');

		// ASSERT that the "draft" report has NOT detected the page having a broken link.
		// ASSERT that the "published" report has NOT detected the page having a broken link.

		$this->isReportBroken($brokenVirtualPagesReport, false, false);
	}

	/**
	 *	Test the broken redirector pages side report.
	 */

	public function testBrokenRedirectorPages() {

		// Create a "draft" redirector page with a broken link.

		$page = RedirectorPage::create();
		$page->RedirectionType = 'Internal';
		$page->LinkToID = 987654321;
		$page->writeToStage('Stage');

		// Retrieve the broken redirector pages side report.

		$reports = SS_Report::get_reports();
		$brokenRedirectorPagesReport = null;
		foreach($reports as $report) {
			if($report instanceof BrokenRedirectorPagesReport) {
				$brokenRedirectorPagesReport = $report;
				break;
			}
		}

		// Determine that the report exists, otherwise it has been excluded.
		if(!$brokenRedirectorPagesReport){
			$this->markTestSkipped('BrokenRedirectorPagesReport is not an available report');
			return;
		}

		// ASSERT that the "draft" report has detected the page having a broken link.
		// ASSERT that the "published" report has NOT detected the page having a broken link, as the page has not been "published" yet.

		$this->isReportBroken($brokenRedirectorPagesReport, true, false);

		// Make sure the page is now "published".

		$page->writeToStage('Live');

		// ASSERT that the "draft" report has detected the page having a broken link.
		// ASSERT that the "published" report has detected the page having a broken link.

		$this->isReportBroken($brokenRedirectorPagesReport, true, true);

		// Correct the "draft" broken link.

		$contentPage = Page::create();
		$contentPage->Content = 'This is some content.';
		$contentPage->writeToStage('Stage');
		$contentPage->writeToStage('Live');
		$page->LinkToID = $contentPage->ID;
		$page->writeToStage('Stage');

		// ASSERT that the "draft" report has NOT detected the page having a broken link.
		// ASSERT that the "published" report has detected the page having a broken link, as the previous content remains "published".

		$this->isReportBroken($brokenRedirectorPagesReport, false, true);

		// Make sure the change has now been "published".

		$page->writeToStage('Live');

		// ASSERT that the "draft" report has NOT detected the page having a broken link.
		// ASSERT that the "published" report has NOT detected the page having a broken link.

		$this->isReportBroken($brokenRedirectorPagesReport, false, false);
	}

}
