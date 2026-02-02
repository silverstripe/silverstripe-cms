<?php

namespace SilverStripe\CMS\Tests\Behaviour;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;

/**
 * Adds a new field to the app records form that forces validation to fail
 */
class ValidationFailedAddPageExtension extends Extension implements TestOnly
{
    protected function updatePageOptions(FieldList $fields)
    {
        $fields->add(new class('ValidationFailureField') extends TextField {
            public function validate($validator)
            {
                $validator->validationError($this->getName(), 'This field failed validation');
                return false;
            }
        });
    }
}
