<?php
/**
 * @package cms
 * @subpackage testing
 *
 * @todo Fix unpublished pages check in testPublishedPagesMatchedByTitle()
 * @todo All tests run on unpublished pages at the moment, due to the searchform not distinguishing between them
 *
 * Because this manipulates the test database in severe ways, I've renamed the test to force it to run last...
 */
class ZZZSearchFormTest extends FunctionalTest {
	
	protected static $fixture_file = 'SearchFormTest.yml';

	protected $illegalExtensions = array(
		'SiteTree' => array('SiteTreeSubsites', 'Translatable')
	);

	protected $mockController;
	
	public function waitUntilIndexingFinished() {
		$schema = DB::get_schema();
		if (method_exists($schema, 'waitUntilIndexingFinished')) $schema->waitUntilIndexingFinished();
	}
	
	public function setUpOnce() {
		// HACK Postgres doesn't refresh TSearch indexes when the schema changes after CREATE TABLE
		// MySQL will need a different table type
		self::kill_temp_db();
		FulltextSearchable::enable();
		self::create_temp_db();
		$this->resetDBSchema(true);
		parent::setUpOnce();
	}
	
	public function setUp() {
		parent::setUp();
		
		$holderPage = $this->objFromFixture('SiteTree', 'searchformholder');
		$this->mockController = new ContentController($holderPage);
		
		$this->waitUntilIndexingFinished();
	}
	
	/**
	 * @return Boolean
	 */
	protected function checkFulltextSupport() {
		$conn = DB::get_conn();
		if(class_exists('MSSQLDatabase') && $conn instanceof MSSQLDatabase) {
			$supports = $conn->fullTextEnabled();
		} else {
			$supports = true;
		}
		if(!$supports) $this->markTestSkipped('Fulltext not supported by DB driver or setup');
		return $supports;
	}
	
	public function testSearchFormTemplateCanBeChanged() {
		if(!$this->checkFulltextSupport()) return;

		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$sf->setTemplate('BlankPage');
		
		$this->assertContains(
			'<body class="SearchForm Form RequestHandler BlankPage">',
			$sf->forTemplate()
		);
	}
	
	public function testPublishedPagesMatchedByTitle() {
		if(!$this->checkFulltextSupport()) return;

		$sf = new SearchForm($this->mockController, 'SearchForm');
	
		$publishedPage = $this->objFromFixture('SiteTree', 'publicPublishedPage');
		$publishedPage->publish('Stage', 'Live');
		
		$this->waitUntilIndexingFinished();
		$results = $sf->getResults(null, array('Search'=>'publicPublishedPage'));
		$this->assertContains(
			$publishedPage->ID,
			$results->column('ID'),
			'Published pages are found by searchform'
		);
	}
	
	public function testDoubleQuotesPublishedPagesMatchedByTitle() {
		if(!$this->checkFulltextSupport()) return;

		$sf = new SearchForm($this->mockController, 'SearchForm');

		$publishedPage = $this->objFromFixture('SiteTree', 'publicPublishedPage');
		$publishedPage->Title = "finding butterflies";
		$publishedPage->write();
		$publishedPage->publish('Stage', 'Live');
		
		$this->waitUntilIndexingFinished();
		$results = $sf->getResults(null, array('Search'=>'"finding butterflies"'));
		$this->assertContains(
			$publishedPage->ID,
			$results->column('ID'),
			'Published pages are found by searchform'
		);
	}
	
	public function testUnpublishedPagesNotIncluded() {
		if(!$this->checkFulltextSupport()) return;

		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$results = $sf->getResults(null, array('Search'=>'publicUnpublishedPage'));
		$unpublishedPage = $this->objFromFixture('SiteTree', 'publicUnpublishedPage');
		$this->assertNotContains(
			$unpublishedPage->ID,
			$results->column('ID'),
			'Unpublished pages are not found by searchform'
		);
	}
	
	public function testPagesRestrictedToLoggedinUsersNotIncluded() {
		if(!$this->checkFulltextSupport()) return;

		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$page = $this->objFromFixture('SiteTree', 'restrictedViewLoggedInUsers');
		$page->publish('Stage', 'Live');
		$results = $sf->getResults(null, array('Search'=>'restrictedViewLoggedInUsers'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to logged in users" doesnt show without valid login'
		);
		
		$member = $this->objFromFixture('Member', 'randomuser');
		$member->logIn();
		$results = $sf->getResults(null, array('Search'=>'restrictedViewLoggedInUsers'));
		$this->assertContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to logged in users" shows if login is present'
		);
		$member->logOut();
	}

	public function testPagesRestrictedToSpecificGroupNotIncluded() {
		if(!$this->checkFulltextSupport()) return;

		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$page = $this->objFromFixture('SiteTree', 'restrictedViewOnlyWebsiteUsers');
		$page->publish('Stage', 'Live');
		$results = $sf->getResults(null, array('Search'=>'restrictedViewOnlyWebsiteUsers'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to these users" doesnt show without valid login'
		);
		
		$member = $this->objFromFixture('Member', 'randomuser');
		$member->logIn();
		$results = $sf->getResults(null, array('Search'=>'restrictedViewOnlyWebsiteUsers'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to these users" doesnt show if logged in user is not in the right group'
		);
		$member->logOut();
		
		$member = $this->objFromFixture('Member', 'websiteuser');
		$member->logIn();
		$results = $sf->getResults(null, array('Search'=>'restrictedViewOnlyWebsiteUsers'));
		$this->assertContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Restrict to these users" shows if user in this group is logged in'
		);
		$member->logOut();
	}
	
	public function testInheritedRestrictedPagesNotIncluded() {
		$sf = new SearchForm($this->mockController, 'SearchForm');

		$parent = $this->objFromFixture('SiteTree', 'restrictedViewLoggedInUsers');
		$parent->publish('Stage', 'Live');
		
		$page = $this->objFromFixture('SiteTree', 'inheritRestrictedView');
		$page->publish('Stage', 'Live');
		$results = $sf->getResults(null, array('Search'=>'inheritRestrictedView'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page inheriting "Restrict to loggedin users" doesnt show without valid login'
		);
		
		$member = $this->objFromFixture('Member', 'websiteuser');
		$member->logIn();
		$results = $sf->getResults(null, array('Search'=>'inheritRestrictedView'));
		$this->assertContains(
			$page->ID,
			$results->column('ID'),
			'Page inheriting "Restrict to loggedin users" shows if user in this group is logged in'
		);
		$member->logOut();
	}
	
	public function testDisabledShowInSearchFlagNotIncludedForSiteTree() {
		if(!$this->checkFulltextSupport()) return;

		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$page = $this->objFromFixture('SiteTree', 'dontShowInSearchPage');
		$results = $sf->getResults(null, array('Search'=>'dontShowInSearchPage'));
		$this->assertNotContains(
			$page->ID,
			$results->column('ID'),
			'Page with "Show in Search" disabled doesnt show'
		);
	}
	
	public function testDisabledShowInSearchFlagNotIncludedForFiles() {
		if(!$this->checkFulltextSupport()) return;

		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$dontShowInSearchFile = $this->objFromFixture('File', 'dontShowInSearchFile');
		$showInSearchFile = $this->objFromFixture('File', 'showInSearchFile');
		$results = $sf->getResults(null, array('Search'=>'dontShowInSearchFile'));
		$this->assertNotContains(
			$dontShowInSearchFile->ID,
			$results->column('ID'),
			'File with "Show in Search" disabled doesnt show'
		);
		
		$results = $sf->getResults(null, array('Search'=>'showInSearchFile'));
		$this->assertContains(
			$showInSearchFile->ID,
			$results->column('ID'),
			'File with "Show in Search" enabled can be found'
		);
	}

	public function testSearchTitleAndContentWithSpecialCharacters() {
		if(!$this->checkFulltextSupport()) return;

		if(class_exists('PostgreSQLDatabase') && DB::get_conn() instanceof PostgreSQLDatabase) {
			$this->markTestSkipped("PostgreSQLDatabase doesn't support entity-encoded searches");
		}

		$sf = new SearchForm($this->mockController, 'SearchForm');
		
		$pageWithSpecialChars = $this->objFromFixture('SiteTree', 'pageWithSpecialChars');
		$pageWithSpecialChars->publish('Stage', 'Live');
		
		$results = $sf->getResults(null, array('Search'=>'Brötchen'));
		$this->assertContains(
			$pageWithSpecialChars->ID,
			$results->column('ID'),
			'Published pages with umlauts in title are found'
		);
		
		$results = $sf->getResults(null, array('Search'=>'Bäcker'));
		$this->assertContains(
			$pageWithSpecialChars->ID,
			$results->column('ID'),
			'Published pages with htmlencoded umlauts in content are found'
		);
	}
}
