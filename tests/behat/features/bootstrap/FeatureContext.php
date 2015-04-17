<?php

namespace SilverStripe\Cms\Test\Behaviour;

use SilverStripe\BehatExtension\Context\SilverStripeContext,
	SilverStripe\BehatExtension\Context\BasicContext,
	SilverStripe\BehatExtension\Context\LoginContext,
	SilverStripe\BehatExtension\Context\FixtureContext,
	SilverStripe\Framework\Test\Behaviour\CmsFormsContext,
	SilverStripe\Framework\Test\Behaviour\CmsUiContext,
	SilverStripe\Cms\Test\Behaviour;

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Features context
 *
 * Context automatically loaded by Behat.
 * Uses subcontexts to extend functionality.
 */
class FeatureContext extends \SilverStripe\Framework\Test\Behaviour\FeatureContext {

	/**
	 * Initializes context.
	 * Every scenario gets it's own context object.
	 *
	 * @param   array   $parameters     context parameters (set them up through behat.yml)
	 */
	public function __construct(array $parameters) {
		parent::__construct($parameters);

		// Override existing fixture context with more specific one
		$fixtureContext = new \SilverStripe\Cms\Test\Behaviour\FixtureContext($parameters);
		$fixtureContext->setFixtureFactory($this->getFixtureFactory());
		$this->useContext('FixtureContext', $fixtureContext);

		// Add extra contexts with more steps
		$this->useContext('ThemeContext', new \SilverStripe\Cms\Test\Behaviour\ThemeContext($parameters));

		// Use blueprints which auto-publish all subclasses of SiteTree
		$factory = $fixtureContext->getFixtureFactory();
		foreach(\ClassInfo::subclassesFor('SiteTree') as $id => $class) {
			$blueprint = \Injector::inst()->create('FixtureBlueprint', $class);
			$blueprint->addCallback('afterCreate', function($obj, $identifier, &$data, &$fixtures) {
				$obj->publish('Stage', 'Live');
			});
			$factory->define($class, $blueprint);
		} 

	}
}
