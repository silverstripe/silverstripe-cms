<?php

use SilverStripe\Assets\File;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\MSSQL\MSSQLDatabase;
use SilverStripe\PostgreSQL\PostgreSQLDatabase;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Search\SearchForm;
use SilverStripe\ORM\Search\FulltextSearchable;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;

/**
 * @package cms
 * @subpackage testing
 *
 * @todo Fix unpublished pages check in testPublishedPagesMatchedByTitle()
 * @todo All tests run on unpublished pages at the moment, due to the searchform not distinguishing between them
 *
 * Because this manipulates the test database in severe ways, I've renamed the test to force it to run last...
 */
class ZZZSearchFormTest extends FunctionalTest
{

    protected static $fixture_file = 'SearchFormTest.yml';

    protected $illegalExtensions = array(
        SiteTree::class => array('SiteTreeSubsites', 'Translatable')
    );

    /**
     * @var ContentController
     */
    protected $mockController;

    public function waitUntilIndexingFinished()
    {
        $schema = DB::get_schema();
        if (method_exists($schema, 'waitUntilIndexingFinished')) {
            $schema->waitUntilIndexingFinished();
        }
    }

    public function setUpOnce()
    {
        // HACK Postgres doesn't refresh TSearch indexes when the schema changes after CREATE TABLE
        // MySQL will need a different table type
        self::kill_temp_db();
        Config::modify();
        FulltextSearchable::enable();
        self::create_temp_db();
        $this->resetDBSchema(true);
        parent::setUpOnce();
    }

    public function setUp()
    {
        parent::setUp();

        /** @var Page $holderPage */
        $holderPage = $this->objFromFixture(SiteTree::class, 'searchformholder');
        $this->mockController = ModelAsController::controller_for($holderPage);

        $this->waitUntilIndexingFinished();
    }

    /**
     * @return Boolean
     */
    protected function checkFulltextSupport()
    {
        $conn = DB::get_conn();
        if (class_exists(MSSQLDatabase::class) && $conn instanceof MSSQLDatabase) {
            $supports = $conn->fullTextEnabled();
        } else {
            $supports = true;
        }
        if (!$supports) {
            $this->markTestSkipped('Fulltext not supported by DB driver or setup');
        }
        return $supports;
    }

    /**
     * @skipUpgrade
     */
    public function testSearchFormTemplateCanBeChanged()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $sf = new SearchForm($this->mockController);

        $sf->setTemplate('BlankPage');

        $this->assertContains(
            '<body class="SearchForm Form BlankPage">',
            $sf->forTemplate()
        );
    }

    /**
     * @skipUpgrade
     */
    public function testPublishedPagesMatchedByTitle()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'publicPublishedPage']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        $publishedPage = $this->objFromFixture(SiteTree::class, 'publicPublishedPage');
        $publishedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $this->waitUntilIndexingFinished();

        $results = $sf->getResults();
        $this->assertContains(
            $publishedPage->ID,
            $results->column('ID'),
            'Published pages are found by searchform'
        );
    }

    /**
     * @skipUpgrade
     */
    public function testDoubleQuotesPublishedPagesMatchedByTitle()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'"finding butterflies"']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        $publishedPage = $this->objFromFixture(SiteTree::class, 'publicPublishedPage');
        $publishedPage->Title = "finding butterflies";
        $publishedPage->write();
        $publishedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $this->waitUntilIndexingFinished();
        $results = $sf->getResults();
        $this->assertContains(
            $publishedPage->ID,
            $results->column('ID'),
            'Published pages are found by searchform'
        );
    }

    /**
     * @skipUpgrade
     */
    public function testUnpublishedPagesNotIncluded()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'publicUnpublishedPage']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        $results = $sf->getResults();
        $unpublishedPage = $this->objFromFixture(SiteTree::class, 'publicUnpublishedPage');
        $this->assertNotContains(
            $unpublishedPage->ID,
            $results->column('ID'),
            'Unpublished pages are not found by searchform'
        );
    }

    public function testPagesRestrictedToLoggedinUsersNotIncluded()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'restrictedViewLoggedInUsers']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        $page = $this->objFromFixture(SiteTree::class, 'restrictedViewLoggedInUsers');
        $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $results = $sf->getResults();
        $this->assertNotContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to logged in users" doesnt show without valid login'
        );

        $member = $this->objFromFixture(Member::class, 'randomuser');
        $member->logIn();
        $results = $sf->getResults();
        $this->assertContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to logged in users" shows if login is present'
        );
        $member->logOut();
    }

    public function testPagesRestrictedToSpecificGroupNotIncluded()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'restrictedViewOnlyWebsiteUsers']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        $page = $this->objFromFixture(SiteTree::class, 'restrictedViewOnlyWebsiteUsers');
        $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $results = $sf->getResults();
        $this->assertNotContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to these users" doesnt show without valid login'
        );

        $member = $this->objFromFixture(Member::class, 'randomuser');
        $member->logIn();
        $results = $sf->getResults();
        $this->assertNotContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to these users" doesnt show if logged in user is not in the right group'
        );
        $member->logOut();

        $member = $this->objFromFixture(Member::class, 'websiteuser');
        $member->logIn();
        $results = $sf->getResults();
        $this->assertContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to these users" shows if user in this group is logged in'
        );
        $member->logOut();
    }

    public function testInheritedRestrictedPagesNotIncluded()
    {
        $request = new HTTPRequest('GET', 'search', ['Search'=>'inheritRestrictedView']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        $parent = $this->objFromFixture(SiteTree::class, 'restrictedViewLoggedInUsers');
        $parent->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $page = $this->objFromFixture(SiteTree::class, 'inheritRestrictedView');
        $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $results = $sf->getResults();
        $this->assertNotContains(
            $page->ID,
            $results->column('ID'),
            'Page inheriting "Restrict to loggedin users" doesnt show without valid login'
        );

        $member = $this->objFromFixture(Member::class, 'websiteuser');
        $member->logIn();
        $results = $sf->getResults();
        $this->assertContains(
            $page->ID,
            $results->column('ID'),
            'Page inheriting "Restrict to loggedin users" shows if user in this group is logged in'
        );
        $member->logOut();
    }

    public function testDisabledShowInSearchFlagNotIncludedForSiteTree()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'dontShowInSearchPage']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        $page = $this->objFromFixture(SiteTree::class, 'dontShowInSearchPage');
        $results = $sf->getResults();
        $this->assertNotContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Show in Search" disabled does not show'
        );
    }

    public function testDisabledShowInSearchFlagNotIncludedForFiles()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'dontShowInSearchFile']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        $dontShowInSearchFile = $this->objFromFixture(File::class, 'dontShowInSearchFile');
        $dontShowInSearchFile->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $showInSearchFile = $this->objFromFixture(File::class, 'showInSearchFile');
        $showInSearchFile->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $results = $sf->getResults();
        $this->assertNotContains(
            $dontShowInSearchFile->ID,
            $results->column('ID'),
            'File with "Show in Search" disabled doesnt show'
        );

        // Check ShowInSearch=1 can be found
        $request = new HTTPRequest('GET', 'search', ['Search'=>'showInSearchFile']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);
        $results = $sf->getResults();
        $this->assertContains(
            $showInSearchFile->ID,
            $results->column('ID'),
            'File with "Show in Search" enabled can be found'
        );
    }

    public function testSearchTitleAndContentWithSpecialCharacters()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        if (class_exists(PostgreSQLDatabase::class) && DB::get_conn() instanceof PostgreSQLDatabase) {
            $this->markTestSkipped("PostgreSQLDatabase doesn't support entity-encoded searches");
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'Brötchen']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        $pageWithSpecialChars = $this->objFromFixture(SiteTree::class, 'pageWithSpecialChars');
        $pageWithSpecialChars->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $results = $sf->getResults();
        $this->assertContains(
            $pageWithSpecialChars->ID,
            $results->column('ID'),
            'Published pages with umlauts in title are found'
        );

        // Check another word
        $request = new HTTPRequest('GET', 'search', ['Search'=>'Bäcker']);
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);
        $results = $sf->getResults();
        $this->assertContains(
            $pageWithSpecialChars->ID,
            $results->column('ID'),
            'Published pages with htmlencoded umlauts in content are found'
        );
    }
}
