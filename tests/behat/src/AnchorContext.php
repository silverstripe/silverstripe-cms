<?php

namespace SilverStripe\CMS\Tests\Behaviour;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use SilverStripe\BehatExtension\Context\MainContextAwareTrait;
use SilverStripe\BehatExtension\Utility\StepHelper;

/**
 * Context used to select items in the anchor field
 */
class AnchorContext implements Context
{
    use MainContextAwareTrait;
    use StepHelper;

    /**
     * Select a value in the anchor selector field
     *
     * @When /^I select "([^"]*)" in the "([^"]*)" anchor dropdown$/
     */
    public function iSelectValueInAnchorDropdown($text, $selector)
    {
        $page = $this->getMainContext()->getSession()->getPage();
        /** @var NodeElement $parentElement */
        $parentElement = null;
        $this->retryThrowable(function () use (&$parentElement, &$page, $selector) {
            $parentElement = $page->find('css', $selector);
            Assert::assertNotNull($parentElement, sprintf('"%s" element not found', $selector));
            $page = $this->getMainContext()->getSession()->getPage();
        });

        $this->retryThrowable(function () use ($parentElement, $selector) {
            $dropdown = $parentElement->find('css', '.anchorselectorfield__dropdown-indicator');
            Assert::assertNotNull($dropdown, sprintf('Unable to find the dropdown in "%s"', $selector));
            $dropdown->click();
        });

        $this->retryThrowable(function () use ($text, $parentElement, $selector) {
            $element = $parentElement->find('xpath', sprintf('//*[count(*)=0 and .="%s"]', $text));
            Assert::assertNotNull($element, sprintf('"%s" not found in "%s"', $text, $selector));
            $element->click();
        });
    }
}
