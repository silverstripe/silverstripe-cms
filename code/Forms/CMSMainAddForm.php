<?php

namespace SilverStripe\CMS\Forms;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\SelectionGroup;
use SilverStripe\Forms\SelectionGroup_Item;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Security;

class CMSMainAddForm extends Form
{
    public function __construct(CMSMain $controller)
    {
        $this->setController($controller);
        parent::__construct($controller, 'AddForm', $this->createFields(), $this->createActions());
        $negotiator = $controller->getResponseNegotiator();
        $this->setHTMLID('Form_AddForm')->setStrictFormMethodCheck(false);
        $this->setAttribute('data-hints', $controller->TreeHints());
        $this->setAttribute('data-childfilter', $controller->Link('childfilter'));
        $this->setValidationResponseCallback(function () use ($negotiator, $controller) {
            $request = $controller->getRequest();
            if ($request->isAjax() && $negotiator) {
                $result = $this->forTemplate();
                return $negotiator->respond($request, [
                    'CurrentForm' => function () use ($result) {
                        return $result;
                    }
                ]);
            }
            return null;
        });
        $this->addExtraClass('flexbox-area-grow fill-height cms-add-form cms-content cms-edit-form ' . $controller->BaseCSSClasses());
        $this->setTemplate($controller->getTemplatesWithSuffix('_AddForm'));
    }

    public function doAdd(array $data, Form $form): HTTPResponse
    {
        /** @var CMSMain $controller */
        $controller = $this->getController();
        $defaultRecordType = $controller->getDefaultModelClass();
        $modelClass = $controller->getModelClass();
        $className = isset($data['RecordType']) ? $data['RecordType'] : $defaultRecordType;
        $parentID = isset($data['ParentID']) ? (int)$data['ParentID'] : 0;

        if (is_numeric($parentID) && $parentID > 0) {
            $parentObj = DataObject::get($modelClass)->byID($parentID);
        } else {
            $parentObj = null;
        }

        if (!$parentObj || !$parentObj->ID) {
            $parentID = 0;
        }

        if (!DataObject::singleton($className)->canCreate(Security::getCurrentUser(), ['Parent' => $parentObj])) {
            return Security::permissionFailure($controller);
        }

        $record = $controller->getNewItem("new-$className-$parentID", false);
        $controller->extend('updateDoAdd', $record, $form);
        $record->write();

        $editController = CMSPageEditController::singleton();
        $editController->setRequest($controller->getRequest());
        $editController->setCurrentRecordID($record->ID);

        $controller->getResponse()->addHeader('X-Status', rawurlencode(_t(
            LeftAndMain::class . '.CREATED_RECORD',
            'Created {name} "{title}"',
            [
                'name' => $record->i18n_singular_name(),
                'title' => $record->Title,
            ]
        )));
        return $controller->redirect($editController->Link('show/' . $record->ID));
    }

    public function doCancel(): HTTPResponse
    {
        return $this->getController()->redirect(CMSMain::singleton()->Link());
    }

    protected function createFields(): FieldList
    {
        /** @var CMSMain $controller */
        $controller = $this->getController();
        $modelClass = $controller->getModelClass();
        $singleton = DataObject::singleton($modelClass);

        $numericLabelTmpl = '<span class="step-label"><span class="flyout">Step %d. </span><span class="title">%s</span></span>';
        $topTitle = _t(__CLASS__ . '.ParentMode_top', 'Top level');
        $childTitle = _t(
            __CLASS__ . '.ParentMode_child',
            'Under another {type}',
            ['type' => mb_strtolower($singleton->i18n_singular_name())]
        );

        $fields = FieldList::create(
            $parentModeField = SelectionGroup::create(
                'ParentModeField',
                [
                    $topField = SelectionGroup_Item::create(
                        'top',
                        null,
                        $topTitle
                    ),
                    SelectionGroup_Item::create(
                        'child',
                        $parentField = TreeDropdownField::create(
                            'ParentID',
                            '',
                            $modelClass
                        ),
                        $childTitle
                    )
                ]
            ),
            LiteralField::create(
                'RestrictedNote',
                sprintf(
                    '<p class="alert alert-info message-restricted">%s</p>',
                    _t(
                        __CLASS__ . '.AddRecordRestriction',
                        'Note: Some {model} types are not allowed for this selection',
                        ['model' => mb_strtolower($singleton->i18n_singular_name())]
                    )
                )
            ),
            OptionsetField::create(
                'RecordType',
                DBField::create_field(
                    'HTMLFragment',
                    sprintf($numericLabelTmpl, 2, _t(
                        __CLASS__ . '.ChooseRecordType',
                        'Choose {model} type',
                        ['model' => mb_strtolower($singleton->i18n_singular_name())]
                    ))
                ),
                $this->getRecordTypes($controller, $singleton),
                $controller->getDefaultModelClass()
            )
        );

        $parentModeField->setTitle(DBField::create_field(
            'HTMLFragment',
            sprintf($numericLabelTmpl, 1, _t(__CLASS__ . '.ChooseParentMode', 'Choose where to create this record'))
        ));

        $parentField->setSearchFunction(function ($sourceObject, $labelField, $search) {
            return DataObject::get($sourceObject)
                ->filterAny([
                    'MenuTitle:PartialMatch' => $search,
                    'Title:PartialMatch' => $search,
                ]);
        });

        $parentModeField->addExtraClass('parent-mode');

        // Ensure the correct parent is set
        if ($parentID = $controller->getRequest()->getVar('ParentID')) {
            $parentModeField->setValue('child');
            $parentField->setValue((int)$parentID);
        } else {
            $parentModeField->setValue('top');
        }

        // Check if the current user has enough permissions to create top level records
        // If not, then disable the option to do that
        if (!$controller->canCreateTopLevel()) {
            $topField->setDisabled(true);
            $parentModeField->setValue('child');
        }

        $this->extend('updateFields', $fields);
        return $fields;
    }

    protected function createActions(): FieldList
    {
        $actions = FieldList::create(
            FormAction::create('doAdd', _t(CMSMain::class . '.Create', 'Create'))
                ->addExtraClass('btn-primary font-icon-plus-circled')
                ->setUseButtonTag(true),
            FormAction::create('doCancel', _t(CMSMain::class . '.Cancel', 'Cancel'))
                ->addExtraClass('btn-secondary')
                ->setUseButtonTag(true)
        );
        $this->extend('updateActions', $fields);
        return $actions;
    }

    protected function getRecordTypes(CMSMain $controller, DataObject $singleton): array
    {
        $defaultIcon = $controller->getRecordIconCssClass($singleton);
        $recordTypes = [];
        foreach ($controller->RecordTypes() as $type) {
            $class = $type->getField('ClassName');
            $typeSingleton = DataObject::singleton($class);
            $icon = $controller->getRecordIconCssClass($typeSingleton) ?: $defaultIcon;

            // If the icon is the default and there's some specific icon being provided by `getRecordIconUrl`
            // then we don't need to add the icon class. Otherwise the class take precedence.
            if ($icon === $defaultIcon && !empty($controller->getRecordIconUrl($typeSingleton))) {
                $icon = '';
            }

            $html = sprintf(
                '<span class="record-icon %s class-%s"></span><span class="title">%s</span><span class="form__field-description">%s</span>',
                $icon,
                Convert::raw2htmlid($class),
                $type->getField('AddAction'),
                $type->getField('Description')
            );
            $recordTypes[$class] = DBField::create_field('HTMLFragment', $html);
        }

        // Ensure default record type shows on top
        $defaultRecordType = $controller->getDefaultModelClass();
        if (isset($recordTypes[$defaultRecordType])) {
            $typeName = $recordTypes[$defaultRecordType];
            $recordTypes = array_merge([$defaultRecordType => $typeName], $recordTypes);
        }

        return $recordTypes;
    }
}
