<?php

namespace SilverStripe\CMS\Tests\Controllers\ContentControllerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\TemplateEngine;
use SilverStripe\View\ViewLayerData;

/**
 * A dummy template renderer that doesn't actually render any templates.
 */
class DummyTemplateEngine implements TemplateEngine, TestOnly
{
    private string $output = '<html><head></head><body></body></html>';

    private string|array $templates;

    public function __construct(string|array $templateCandidates = [])
    {
        $this->templates = $templateCandidates;
    }

    public function setTemplate(string|array $templateCandidates): static
    {
        $this->templates = $templateCandidates;
        return $this;
    }

    public function hasTemplate(string|array $templateCandidates): bool
    {
        return true;
    }

    public function renderString(string $template, ViewLayerData $model, array $overlay = [], bool $cache = true): string
    {
        return $this->output;
    }

    public function render(ViewLayerData $model, array $overlay = []): string
    {
        return $this->output;
    }

    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    /**
     * Returns the template candidates that were passed to the constructor or to setTemplate()
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }
}
