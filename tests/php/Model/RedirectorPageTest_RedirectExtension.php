<?php

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class RedirectorPageTest_RedirectExtension extends Extension implements TestOnly
{

    public function onBeforeInit()
    {
        $this->owner->redirect('/foo');
    }
}
