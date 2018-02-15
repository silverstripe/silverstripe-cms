<?php

namespace SilverStripe\CMS\Controllers;

use SilverStripe\Admin\LeftAndMainFormRequestHandler;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;

class CMSPageHistoryController extends CMSMain
{

    private static $url_segment = 'pages/history';

    private static $url_rule = '/$Action/$ID/$VersionID/$OtherVersionID';

    private static $url_priority = 42;

    private static $menu_title = 'History';

    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    private static $allowed_actions = array(
        'EditForm',
        'VersionsForm',
        'CompareVersionsForm',
        'show',
        'compare'
    );

    private static $url_handlers = array(
        '$Action/$ID/$VersionID/$OtherVersionID' => 'handleAction',
        'EditForm/$ID/$VersionID' => 'EditForm',
    );

    /**
     * Current version ID for this request. Can be 0 for latest version
     *
     * @var int
     */
    protected $versionID = null;

    public function getResponseNegotiator()
    {
        $negotiator = parent::getResponseNegotiator();

        $negotiator->setCallback('CurrentForm', function () {
            $form = $this->getEditForm();
            if ($form) {
                return $form->forTemplate();
            }
            return $this->renderWith($this->getTemplatesWithSuffix('_Content'));
        });

        $negotiator->setCallback('default', function () {
            return $this->renderWith($this->getViewer('show'));
        });

        return $negotiator;
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function show($request)
    {
        // Record id and version for this request
        $id = $request->param('ID');
        $this->setCurrentPageID($id);
        $versionID = $request->param('VersionID');
        $this->setVersionID($versionID);

        // Show id
        $form = $this->getEditForm();

        $negotiator = $this->getResponseNegotiator();
        $negotiator->setCallback('CurrentForm', function () use ($form) {
            return $form
                ? $form->forTemplate()
                : $this->renderWith($this->getTemplatesWithSuffix('_Content'));
        });
        $negotiator->setCallback('default', function () use ($form) {
            return $this
                ->customise(array('EditForm' => $form))
                ->renderWith($this->getViewer('show'));
        });

        return $negotiator->respond($request);
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function compare($request)
    {
        $form = $this->CompareVersionsForm(
            $request->param('VersionID'),
            $request->param('OtherVersionID')
        );

        $negotiator = $this->getResponseNegotiator();
        $negotiator->setCallback('CurrentForm', function () use ($form) {
            return $form ? $form->forTemplate() : $this->renderWith($this->getTemplatesWithSuffix('_Content'));
        });
        $negotiator->setCallback('default', function () use ($form) {
            return $this->customise(array('EditForm' => $form))->renderWith($this->getViewer('show'));
        });

        return $negotiator->respond($request);
    }

    public function getSilverStripeNavigator()
    {
        $record = $this->getRecord($this->currentPageID(), $this->getRequest()->param('VersionID'));
        if ($record) {
            $navigator = new SilverStripeNavigator($record);
            return $navigator->renderWith($this->getTemplatesWithSuffix('_SilverStripeNavigator'));
        } else {
            return false;
        }
    }

    /**
     * @param HTTPRequest $request
     * @return Form
     */
    public function EditForm($request = null)
    {
        if ($request) {
            // Validate VersionID is present
            $versionID = $request->param('VersionID');
            if (!isset($versionID)) {
                $this->httpError(400);
                return null;
            }
            $this->setVersionID($versionID);
        }
        return parent::EditForm($request);
    }

    /**
     * Returns the read only version of the edit form. Detaches all {@link FormAction}
     * instances attached since only action relates to revert.
     *
     * Permission checking is done at the {@link CMSMain::getEditForm()} level.
     *
     * @param int $id ID of the record to show
     * @param array $fields optional
     * @param int $versionID
     * @param int $compareID Compare mode
     *
     * @return Form
     */
    public function getEditForm($id = null, $fields = null, $versionID = null, $compareID = null)
    {
        if (!$id) {
            $id = $this->currentPageID();
        }
        if (!$versionID) {
            $versionID = $this->getVersionID();
        }

        $record = $this->getRecord($id, $versionID);
        if (!$record) {
            return $this->EmptyForm();
        }

        // Refresh version ID
        $versionID = $record->Version;
        $this->setVersionID($versionID);

        // Get edit form
        $form = parent::getEditForm($record, $record->getCMSFields());
        // Respect permission failures from parent implementation
        if (!($form instanceof Form)) {
            return $form;
        }

        // TODO: move to the SilverStripeNavigator structure so the new preview can pick it up.
        //$nav = new SilverStripeNavigatorItem_ArchiveLink($record);

        $actions = new FieldList(
            $revert = FormAction::create(
                'doRollback',
                _t('SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.REVERTTOTHISVERSION', 'Revert to this version')
            )
                ->setUseButtonTag(true)
                ->addExtraClass('btn-warning font-icon-back-in-time')
        );
        $actions->setForm($form);
        $form->setActions($actions);

        $fields = $form->Fields();
        $fields->removeByName("Status");
        $fields->push(new HiddenField("ID"));
        $fields->push(new HiddenField("Version"));

        $fields = $fields->makeReadonly();

        if ($compareID) {
            $link = Controller::join_links(
                $this->Link('show'),
                $id
            );

            $view = _t(__CLASS__ . '.VIEW', "view");

            $message = _t(
                __CLASS__ . '.COMPARINGVERSION',
                "Comparing versions {version1} and {version2}.",
                array(
                    'version1' => sprintf('%s (<a href="%s">%s</a>)', $versionID, Controller::join_links($link, $versionID), $view),
                    'version2' => sprintf('%s (<a href="%s">%s</a>)', $compareID, Controller::join_links($link, $compareID), $view)
                )
            );

            $revert->setReadonly(true);
        } else {
            if ($record->isLatestVersion()) {
                $message = _t(__CLASS__ . '.VIEWINGLATEST', 'Currently viewing the latest version.');
            } else {
                $message = _t(
                    __CLASS__ . '.VIEWINGVERSION',
                    "Currently viewing version {version}.",
                    array('version' => $versionID)
                );
            }
        }

        /** @var Tab $mainTab */
        $mainTab = $fields->fieldByName('Root.Main');
        $mainTab->unshift(
            LiteralField::create('CurrentlyViewingMessage', ArrayData::create(array(
                'Content' => DBField::create_field('HTMLFragment', $message),
                'Classes' => 'alert alert-info'
            ))->renderWith($this->getTemplatesWithSuffix('_notice')))
        );

        $form->setFields($fields->makeReadonly());
        $form->loadDataFrom(array(
            "ID" => $id,
            "Version" => $versionID,
        ));

        if ($record->isLatestVersion()) {
            $revert->setReadonly(true);
        }

        $form->removeExtraClass('cms-content');

        // History form has both ID and VersionID as suffixes
        $form->setRequestHandler(
            LeftAndMainFormRequestHandler::create($form, [$id, $versionID])
        );

        return $form;
    }


    /**
     * Version select form. Main interface between selecting versions to view
     * and comparing multiple versions.
     *
     * Because we can reload the page directly to a compare view (history/compare/1/2/3)
     * this form has to adapt to those parameters as well.
     *
     * @return Form
     */
    public function VersionsForm()
    {
        $id = $this->currentPageID();
        $page = $this->getRecord($id);
        $versionsHtml = '';

        $action = $this->getRequest()->param('Action');
        $versionID = $this->getRequest()->param('VersionID');
        $otherVersionID = $this->getRequest()->param('OtherVersionID');

        $showUnpublishedChecked = 0;
        $compareModeChecked = ($action == "compare");

        if ($page) {
            $versions = $page->allVersions();
            $versionID = (!$versionID) ? $page->Version : $versionID;

            if ($versions) {
                foreach ($versions as $k => $version) {
                    $active = false;

                    if ($version->Version == $versionID || $version->Version == $otherVersionID) {
                        $active = true;

                        if (!$version->WasPublished) {
                            $showUnpublishedChecked = 1;
                        }
                    }

                    $version->Active = ($active);
                }
            }

            $vd = new ViewableData();

            $versionsHtml = $vd->customise(array(
                'Versions' => $versions
            ))->renderWith($this->getTemplatesWithSuffix('_versions'));
        }

        $fields = new FieldList(
            new CheckboxField(
                'ShowUnpublished',
                _t('SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.SHOWUNPUBLISHED', 'Show unpublished versions'),
                $showUnpublishedChecked
            ),
            new CheckboxField(
                'CompareMode',
                _t('SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.COMPAREMODE', 'Compare mode (select two)'),
                $compareModeChecked
            ),
            new LiteralField('VersionsHtml', $versionsHtml),
            $hiddenID = new HiddenField('ID', false, "")
        );

        $form = Form::create(
            $this,
            'VersionsForm',
            $fields,
            new FieldList()
        )->setHTMLID('Form_VersionsForm');
        $form->loadDataFrom($this->getRequest()->requestVars());
        $hiddenID->setValue($id);
        $form->unsetValidator();

        $form
            ->addExtraClass('cms-versions-form') // placeholder, necessary for $.metadata() to work
            ->setAttribute('data-link-tmpl-compare', Controller::join_links($this->Link('compare'), '%s', '%s', '%s'))
            ->setAttribute('data-link-tmpl-show', Controller::join_links($this->Link('show'), '%s', '%s'));

        return $form;
    }

    /**
     * @param int $versionID
     * @param int $otherVersionID
     * @return mixed
     */
    public function CompareVersionsForm($versionID, $otherVersionID)
    {
        if ($versionID > $otherVersionID) {
            $toVersion = $versionID;
            $fromVersion = $otherVersionID;
        } else {
            $toVersion = $otherVersionID;
            $fromVersion = $versionID;
        }

        if (!$toVersion || !$fromVersion) {
            return null;
        }

        $id = $this->currentPageID();
        /** @var SiteTree $page */
        $page = SiteTree::get()->byID($id);

        $record = null;
        if ($page && $page->exists()) {
            if (!$page->canView()) {
                return Security::permissionFailure($this);
            }

            $record = $page->compareVersions($fromVersion, $toVersion);
        }

        $fromVersionRecord = Versioned::get_version(SiteTree::class, $id, $fromVersion);
        $toVersionRecord = Versioned::get_version(SiteTree::class, $id, $toVersion);

        if (!$fromVersionRecord) {
            user_error("Can't find version $fromVersion of page $id", E_USER_ERROR);
        }

        if (!$toVersionRecord) {
            user_error("Can't find version $toVersion of page $id", E_USER_ERROR);
        }

        if (!$record) {
            return null;
        }
        $form = $this->getEditForm($id, null, $fromVersion, $toVersion);
        $form->setActions(new FieldList());
        $form->addExtraClass('compare');

        $form->loadDataFrom($record);
        $form->loadDataFrom(array(
            "ID" => $id,
            "Version" => $fromVersion,
        ));

        // Comparison views shouldn't be editable.
        // As the comparison output is HTML and not valid values for the various field types
        $readonlyFields = $this->transformReadonly($form->Fields());
        $form->setFields($readonlyFields);

        return $form;
    }

    /**
     * Replace all data fields with HTML readonly fields to display diff
     *
     * @param FieldList $fields
     * @return FieldList
     */
    public function transformReadonly(FieldList $fields)
    {
        foreach ($fields->dataFields() as $field) {
            if ($field instanceof HiddenField) {
                continue;
            }
            $newField = $field->castedCopy(HTMLReadonlyField::class);
            $fields->replaceField($field->getName(), $newField);
        }
        return $fields;
    }

    /**
     * Set current version ID
     *
     * @param int $versionID
     * @return $this
     */
    public function setVersionID($versionID)
    {
        $this->versionID = $versionID;
        return $this;
    }

    /**
     * Get current version ID
     *
     * @return int
     */
    public function getVersionID()
    {
        return $this->versionID;
    }

    public function getTabIdentifier()
    {
        return 'history';
    }
}
