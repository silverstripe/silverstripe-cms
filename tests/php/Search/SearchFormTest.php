<?php

namespace SilverStripe\CMS\Tests\Search;

use Page;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Search\SearchForm;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\MSSQL\MSSQLDatabase;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Search\FulltextSearchable;
use SilverStripe\PostgreSQL\PostgreSQLDatabase;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * @todo Fix unpublished pages check in testPublishedPagesMatchedByTitle()
 * @todo All tests run on unpublished pages at the moment, due to the searchform not distinguishing between them
 *
 * Because this manipulates the test database in severe ways, I've renamed the test to force it to run last...
 */
class ZZZSearchFormTest extends FunctionalTest
{

    protected static $fixture_file = 'SearchFormTest.yml';

    protected static $illegal_extensions = array(
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

    public static function setUpBeforeClass()
    {
        // HACK Postgres doesn't refresh TSearch indexes when the schema changes after CREATE TABLE
        // MySQL will need a different table type
        static::$tempDB->kill();
        Config::modify();
        FulltextSearchable::enable();
        static::$tempDB->build();
        static::resetDBSchema(true);
        parent::setUpBeforeClass();
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
        $request->setSession($this->session());
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        /** @var SiteTree $publishedPage */
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
        $request->setSession($this->session());
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        /** @var SiteTree $publishedPage */
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
        $request->setSession($this->session());
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
        $request->setSession($this->session());
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        /** @var SiteTree $page */
        $page = $this->objFromFixture(SiteTree::class, 'restrictedViewLoggedInUsers');
        $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $results = $sf->getResults();
        $this->assertNotContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to logged in users" doesnt show without valid login'
        );

        $member = $this->objFromFixture(Member::class, 'randomuser');
        Security::setCurrentUser($member);
        $results = $sf->getResults();
        $this->assertContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to logged in users" shows if login is present'
        );
        Security::setCurrentUser(null);
    }

    public function testPagesRestrictedToSpecificGroupNotIncluded()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'restrictedViewOnlyWebsiteUsers']);
        $request->setSession($this->session());
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        /** @var SiteTree $page */
        $page = $this->objFromFixture(SiteTree::class, 'restrictedViewOnlyWebsiteUsers');
        $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $results = $sf->getResults();
        $this->assertNotContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to these users" doesnt show without valid login'
        );

        $member = $this->objFromFixture(Member::class, 'randomuser');
        Security::setCurrentUser($member);
        $results = $sf->getResults();
        $this->assertNotContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to these users" doesnt show if logged in user is not in the right group'
        );
        Security::setCurrentUser(null);

        $member = $this->objFromFixture(Member::class, 'websiteuser');
        Security::setCurrentUser($member);
        $results = $sf->getResults();
        $this->assertContains(
            $page->ID,
            $results->column('ID'),
            'Page with "Restrict to these users" shows if user in this group is logged in'
        );
        Security::setCurrentUser(null);
    }

    /**
     *
     */
    public function testInheritedRestrictedPagesNotIncluded()
    {
        $request = new HTTPRequest('GET', 'search', ['Search'=>'inheritRestrictedView']);
        $request->setSession($this->session());
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        /** @var SiteTree $parent */
        $parent = $this->objFromFixture(SiteTree::class, 'restrictedViewLoggedInUsers');
        $parent->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        /** @var SiteTree $page */
        $page = $this->objFromFixture(SiteTree::class, 'inheritRestrictedView');
        $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $results = $sf->getResults();
        $this->assertNotContains(
            $page->ID,
            $results->column('ID'),
            'Page inheriting "Restrict to loggedin users" doesnt show without valid login'
        );

        $member = $this->objFromFixture(Member::class, 'websiteuser');
        Security::setCurrentUser($member);
        $results = $sf->getResults();
        $this->assertContains(
            $page->ID,
            $results->column('ID'),
            'Page inheriting "Restrict to loggedin users" shows if user in this group is logged in'
        );
        Security::setCurrentUser(null);
    }

    public function testDisabledShowInSearchFlagNotIncludedForSiteTree()
    {
        if (!$this->checkFulltextSupport()) {
            return;
        }

        $request = new HTTPRequest('GET', 'search', ['Search'=>'dontShowInSearchPage']);
        $request->setSession($this->session());
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
        $request->setSession($this->session());
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        /** @var File $dontShowInSearchFile */
        $dontShowInSearchFile = $this->objFromFixture(File::class, 'dontShowInSearchFile');
        $dontShowInSearchFile->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        /** @var File $showInSearchFile */
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
        $request->setSession($this->session());
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
        $request->setSession($this->session());
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);

        /** @var SiteTree $pageWithSpecialChars */
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
        $request->setSession($this->session());
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
