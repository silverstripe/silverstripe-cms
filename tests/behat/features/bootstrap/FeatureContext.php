<?php

namespace SilverStripe\Cms\Test\Behaviour;

use SilverStripe\BehatExtension\Context\SilverStripeContext;
use SilverStripe\BehatExtension\Context\BasicContext;
use SilverStripe\BehatExtension\Context\LoginContext;
use SilverStripe\BehatExtension\Context\FixtureContext;
use SilverStripe\Framework\Test\Behaviour\CmsFormsContext;
use SilverStripe\Framework\Test\Behaviour\CmsUiContext;
use SilverStripe\Cms\Test\Behaviour;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;

/**
 * Features context
 *
 * Context automatically loaded by Behat.
 * Uses subcontexts to extend functionality.
 */
class FeatureContext extends \SilverStripe\Framework\Test\Behaviour\FeatureContext
{

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param  array   $parameters     context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        parent::__construct($parameters);

        // Override existing fixture context with more specific one
        $fixtureContext = new \SilverStripe\Cms\Test\Behaviour\FixtureContext($parameters);
        $fixtureContext->setFixtureFactory($this->getFixtureFactory());
        $this->useContext('FixtureContext', $fixtureContext);

        // Add extra contexts with more steps
        $this->useContext('ThemeContext', new \SilverStripe\Cms\Test\Behaviour\ThemeContext($parameters));

        // Use blueprints which auto-publish all subclasses of SiteTree
        $factory = $fixtureContext->getFixtureFactory();
        foreach (ClassInfo::subclassesFor('SilverStripe\\CMS\\Model\\SiteTree') as $id => $class) {
            $blueprint = Injector::inst()->create('SilverStripe\\Dev\\FixtureBlueprint', $class);
            $blueprint->addCallback('afterCreate', function ($obj, $identifier, &$data, &$fixtures) {
                /** @var SiteTree $obj */
                $obj->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
            });
            $factory->define($class, $blueprint);
        }
    }
}
