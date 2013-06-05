<?php

namespace SilverStripe\Cms\Test\Behaviour;

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Context\Step,
    Behat\Behat\Event\StepEvent,
    Behat\Behat\Exception\PendingException,
    Behat\Mink\Driver\Selenium2Driver,
    Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Context used to create fixtures in the SilverStripe ORM.
 */
class FixtureContext extends \SilverStripe\BehatExtension\Context\FixtureContext
{
    protected $context;

    /**
     * @var FixtureFactory
     */
    protected $fixtureFactory;

    public function __construct(array $parameters)
    {
        $this->context = $parameters;
    }

    public function getSession($name = null)
    {
        return $this->getMainContext()->getSession($name);
    }

    /**
     * @return \FixtureFactory
     */
    public function getFixtureFactory() {
        if(!$this->fixtureFactory) {
            $this->fixtureFactory = \Injector::inst()->get('FixtureFactory', 'FixtureContextFactory');
        }
        return $this->fixtureFactory;
    }

    /**
     * @param \FixtureFactory $factory
     */
    public function setFixtureFactory(\FixtureFactory $factory) {
        $this->fixtureFactory = $factory;
    }

     /**
     * Find or create a redirector page and link to another existing page.
     * Example: Given a page "My Redirect" which redirects to a page "Page 1" 
     * 
     * @Given /^(?:(an|a|the) )(?<type>[^"]+)"(?<id>[^"]+)" (:?which )?redirects to (?:(an|a|the) )(?<targetType>[^"]+)"(?<targetId>[^"]+)"$/
     */
    public function stepCreateRedirectorPage($type, $id, $targetType, $targetId)
    {
        $class = 'RedirectorPage';
        $targetClass = $this->convertTypeToClass($targetType);
        
        $targetObj = $this->fixtureFactory->get($targetClass, $targetId);
        if(!$targetObj) $targetObj = $this->fixtureFactory->get($targetClass, $targetId);
        
        $fields = array('LinkToID' => $targetObj->ID);
        $obj = $this->fixtureFactory->get($class, $id);
        if($obj) {
            $obj->update($fields);
        } else {
            $obj = $this->fixtureFactory->createObject($class, $id, $fields);
        }
        $obj->write();
        $obj->publish('Stage', 'Live');
    }
   
    /**
     * Converts a natural language class description to an actual class name.
     * Respects {@link DataObject::$singular_name} variations.
     * Example: "redirector page" -> "RedirectorPage"
     * 
     * @param String 
     * @return String Class name
     */
    protected function convertTypeToClass($type) {
        // Try direct mapping
        $class = str_replace(' ', '', ucfirst($type));
        if(class_exists($class) || !is_subclass_of($class, 'DataObject')) {
            return $class;
        }

        // Fall back to singular names
        foreach(array_values(\ClassInfo::subclassesFor('DataObject')) as $candidate) {
            if(singleton($candidate)->singular_name() == $type) return $candidate;
        }

        throw new \InvalidArgumentException(sprintf(
            'Class "%s" does not exist, or is not a subclass of DataObjet',
            $class
        ));
    }

   
}
