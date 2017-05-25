<?php

namespace SilverStripe\CMS\Forms;

use SilverStripe\Admin\ModalController;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;

/**
 * Decorates ModalController with insert internal link
 * @see ModalController
 */
class InternalLinkModalExtension extends Extension
{
    private static $allowed_actions = array(
        'editorInternalLink',
    );

    /**
     * @return ModalController
     */
    public function getOwner()
    {
        /** @var ModalController $owner */
        $owner = $this->owner;
        return $owner;
    }


    /**
     * Form for inserting internal link pages
     *
     * @return Form
     */
    public function editorInternalLink()
    {
        /** @var InternalLinkFormFactory $factory */
        $factory = InternalLinkFormFactory::singleton();
        return $factory->getForm($this->getOwner(), "editorInternalLink");
    }
}
