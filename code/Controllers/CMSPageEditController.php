<?php

namespace SilverStripe\CMS\Controllers;

use Page;
use SilverStripe\CampaignAdmin\AddToCampaignHandler;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * @package cms
 */
class CMSPageEditController extends CMSMain
{

    private static $url_segment = 'pages/edit';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $url_priority = 41;

    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    private static $allowed_actions = array(
        'AddToCampaignForm',
    );

    public function getClientConfig()
    {
        return array_merge(parent::getClientConfig(), [
            'form' => [
                'AddToCampaignForm' => [
                    'schemaUrl' => $this->Link('schema/AddToCampaignForm')
                ],
            ],
        ]);
    }

    /**
     * Action handler for adding pages to a campaign
     *
     * @param array $data
     * @param Form $form
     * @return DBHTMLText|HTTPResponse
     */
    public function addtocampaign($data, $form)
    {
        $id = $data['ID'];
        $record = \Page::get()->byID($id);

        $handler = AddToCampaignHandler::create($this, $record);
        $results = $handler->addToCampaign($record, $data['Campaign']);
        if (is_null($results)) {
            return null;
        }

        if ($this->getSchemaRequested()) {
            // Send extra "message" data with schema response
            $extraData = ['message' => $results];
            $schemaId = Controller::join_links($this->Link('schema/AddToCampaignForm'), $id);
            return $this->getSchemaResponse($schemaId, $form, null, $extraData);
        }

        return $results;
    }

    /**
     * Url handler for add to campaign form
     *
     * @param HTTPRequest $request
     * @return Form
     */
    public function AddToCampaignForm($request)
    {
        // Get ID either from posted back value, or url parameter
        $id = $request->param('ID') ?: $request->postVar('ID');
        return $this->getAddToCampaignForm($id);
    }

    /**
     * @param int $id
     * @return Form
     */
    public function getAddToCampaignForm($id)
    {
        // Get record-specific fields
        $record = SiteTree::get()->byID($id);

        if (!$record) {
            $this->httpError(404, _t(
                'AssetAdmin.ErrorNotFound',
                'That {Type} couldn\'t be found',
                '',
                ['Type' => Page::singleton()->i18n_singular_name()]
            ));
            return null;
        }
        if (!$record->canView()) {
            $this->httpError(403, _t(
                'AssetAdmin.ErrorItemPermissionDenied',
                'It seems you don\'t have the necessary permissions to add {ObjectTitle} to a campaign',
                '',
                ['ObjectTitle' => Page::singleton()->i18n_singular_name()]
            ));
            return null;
        }

        $handler = AddToCampaignHandler::create($this, $record);
        return $handler->Form($record);
    }
}
