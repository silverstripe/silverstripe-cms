<?php

namespace SilverStripe\CMS\Tests\Controllers\PageTreeControllerTest;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class JsonErrorExtension extends Extension implements TestOnly
{
    protected function onBeforeJSONError(int $errorCode, $request, HTTPResponse $response): void
    {
        $response->addHeader('X-Tree-Error', (string) $errorCode);
    }
}
