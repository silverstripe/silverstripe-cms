<?php

namespace SilverStripe\CMS\Tests\Behaviour;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use PHPUnit\Framework\Assert;
use SilverStripe\BehatExtension\Context\BasicContext;
use SilverStripe\BehatExtension\Context\FixtureContext as BehatFixtureContext;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FixtureBlueprint;
use SilverStripe\Versioned\Versioned;

/**
 * Context used to create fixtures in the SilverStripe ORM.
 */
class FixtureContext extends BehatFixtureContext
{
    /**
     * @var BasicContext
     */
    protected $basicContext;


    /** @BeforeScenario */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $this->basicContext = $scope->getEnvironment()->getContext(BasicContext::class);
    }

    protected function scaffoldDefaultFixtureFactory()
    {
        $factory = parent::scaffoldDefaultFixtureFactory();

        // Use blueprints which auto-publish all subclasses of SiteTree
        foreach (ClassInfo::subclassesFor(SiteTree::class) as $class) {
            $blueprint = Injector::inst()->create(FixtureBlueprint::class, $class);
            $blueprint->addCallback('afterCreate', function ($obj, $identifier, &$data, &$fixtures) {
                /** @var SiteTree $obj */
                $obj->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
            });
            $factory->define($class, $blueprint);
        }

        return $factory;
    }

    /**
     * Find or create a redirector page and link to another existing page.
     * Example: Given a "page" "My Redirect" which redirects to a "page" "Page 1"
     *
     * @Given /^(?:(an|a|the) )"(?<type>[^"]+)" "(?<id>[^"]+)" (:?which )?redirects to (?:(an|a|the) )"(?<targetType>[^"]+)" "(?<targetId>[^"]+)"$/
     * @param string $type
     * @param string $id
     * @param string $targetType
     * @param string $targetId
     */
    public function stepCreateRedirectorPage($type, $id, $targetType, $targetId)
    {
        $class = RedirectorPage::class;
        $targetClass = $this->convertTypeToClass($targetType);

        $targetObj = $this->fixtureFactory->get($targetClass, $targetId);
        if (!$targetObj) {
            $targetObj = $this->fixtureFactory->get($targetClass, $targetId);
        }

        $fields = ['LinkToID' => $targetObj->ID];
        /** @var RedirectorPage $obj */
        $obj = $this->fixtureFactory->get($class, $id);
        if ($obj) {
            $obj->update($fields);
        } else {
            $obj = $this->fixtureFactory->createObject($class, $id, $fields);
        }
        $obj->write();
        $obj->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
    }

    // These functions should be in a FeatureContext, however framework FeatureContext is already
    // loaded and adding another FeatureContext within CMS that extends SilverStripeContext will
    // expose duplicate functions to behat e.g. FillField

    /**
     * @When /^I see the "([^"]+)" element$/
     * @param $selector
     */
    public function iSeeTheElement($selector)
    {
        /** @var DocumentElement $page */
        $page = $this->getMainContext()->getSession()->getPage();
        $element = $page->find('css', $selector);

        Assert::assertNotNull($element, sprintf('Element %s not found', $selector));
    }

    /**
     * Selects the specified radio button
     *
     * @Given /^I see the "([^"]*)" radio button "([^"]*)" attribute equals "([^"]*)"$/
     * @param string $radioLabel
     * @param string $attribute
     * @param string $value
     */
    public function iSeeTheRadioButtonAttributeEquals($radioLabel, $attribute, $value)
    {
        /** @var NodeElement $radioButton */
        $session = $this->getMainContext()->getSession();
        $radioButton = $session->getPage()->find('named', [
            'radio',
            $this->getMainContext()->getXpathEscaper()->escapeLiteral($radioLabel)
        ]);
        Assert::assertNotNull($radioButton);
        Assert::assertEquals($value, $radioButton->getAttribute($attribute));
    }

    /**
     * Assumes you've just opened the Insert link menu, e.g.
     * I click on the "div[aria-label='Insert link [Ctrl+K]'] button" element
     *
     * @When /^I select "(.+?)" from the TinyMCE menu with javascript$/
     * @param string $label
     */
    public function iSelectFromTheTinyMceMenu($label)
    {
        // :visible and :contains are jQuery css selectors
        $js = <<<JS
            jQuery(".mce-menu-item:visible span:contains('{$label}')").click();
JS;
        $this->getMainContext()->getSession()->executeScript($js);
    }

    /**
     * e.g. --PageOne,--PageTwo,---PageTwoChild,--PageThree
     *
     * @Then /^the site tree order should be "(.+?)"$/
     * @param string $label
     */
    public function theSiteTreeOrderShouldBe($expected)
    {
        $js = <<<JS
            jQuery('.jstree-no-checkboxes .item')
                .map((i, el) => {
                    let d = '';
                    jQuery(el).parents('li').each(() => d += '-');
                    return d + jQuery(el).html();
                })
                .get()
                .join()
JS;
        $actual = $this->getMainContext()->getSession()->evaluateScript($js);
        Assert::assertEquals($expected, $actual);
    }
}
