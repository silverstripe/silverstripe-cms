<?php

namespace SilverStripe\CMS\Forms;

use SilverStripe\Admin\LeftAndMainFormRequestHandler;
use SilverStripe\Admin\ModalController;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\Form;

/**
 * Decorates ModalController with insert internal link
 *
 * @extends Extension<ModalController>
 * @deprecated 5.4.0 Will be replaced with configuration on SilverStripe\Admin\ModalController in a future major release
 */
class InternalLinkModalExtension extends Extension
{
    private static $url_handlers = [
        'editorAnchorLink/$ItemID' => 'editorAnchorLink', // Matches LeftAndMain::methodSchema args
    ];

    private static $allowed_actions = [
        'editorInternalLink',
        'editorAnchorLink',
    ];

    public function __construct()
    {
        Deprecation::noticeWithNoReplacment(
            '5.4.0',
            'Will be replaced with configuration on ' . ModalController::class . ' in a future major release',
            Deprecation::SCOPE_CLASS
        );
    }

    /**
     * Form for inserting internal link pages
     *
     * @return Form
     */
    public function editorInternalLink()
    {
        $showLinkText = $this->getOwner()->getRequest()->getVar('requireLinkText');
        $factory = InternalLinkFormFactory::singleton();
        return $factory->getForm(
            $this->getOwner(),
            "editorInternalLink",
            [ 'RequireLinkText' => isset($showLinkText) ]
        );
    }

    public function editorAnchorLink()
    {
        // Note: Should work both via MethodSchema and as direct request
        $request = $this->getOwner()->getRequest();
        $showLinkText = $request->getVar('requireLinkText');
        $pageID = $request->param('ItemID');
        $factory = AnchorLinkFormFactory::singleton();
        $form = $factory->getForm(
            $this->getOwner(),
            "editorAnchorLink",
            [ 'RequireLinkText' => isset($showLinkText), 'PageID' => $pageID ]
        );

        // Set url handler that includes pageID
        $form->setRequestHandler(
            LeftAndMainFormRequestHandler::create($form, [$pageID])
        );

        return $form;
    }
}
