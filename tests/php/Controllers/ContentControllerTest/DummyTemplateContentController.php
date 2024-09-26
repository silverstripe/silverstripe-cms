<?php

namespace SilverStripe\CMS\Tests\Controllers\ContentControllerTest;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Dev\TestOnly;
use SilverStripe\View\TemplateEngine;

class DummyTemplateContentController extends ContentController implements TestOnly
{
    protected function getTemplateEngine(): TemplateEngine
    {
        return new DummyTemplateEngine();
    }
}
