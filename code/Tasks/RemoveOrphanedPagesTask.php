<?php

namespace SilverStripe\CMS\Tasks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

/**
 * Identify "orphaned" pages which point to a parent
 * that no longer exists in a specific stage.
 * Shows the pages to an administrator, who can then
 * decide which pages to remove by ticking a checkbox
 * and manually executing the removal.
 *
 * Caution: Pages also count as orphans if they don't
 * have parents in this stage, even if the parent has a representation
 * in the other stage:
 * - A live child is orphaned if its parent was deleted from live, but still exists on stage
 * - A stage child is orphaned if its parent was deleted from stage, but still exists on live
 *
 * See {@link RemoveOrphanedPagesTaskTest} for an example sitetree
 * before and after orphan removal.
 *
 * @author Ingo Schommer (<firstname>@silverstripe.com), SilverStripe Ltd.
 */
class RemoveOrphanedPagesTask extends Controller
{

    private static $allowed_actions = array(
        'index' => 'ADMIN',
        'Form' => 'ADMIN',
        'run' => 'ADMIN',
        'handleAction' => 'ADMIN',
    );

    protected $title = 'Removed orphaned pages without existing parents from both stage and live';

    protected $description = "
<p>
Identify 'orphaned' pages which point to a parent
that no longer exists in a specific stage.
</p>
<p>
Caution: Pages also count as orphans if they don't
have parents in this stage, even if the parent has a representation
in the other stage:<br />
- A live child is orphaned if its parent was deleted from live, but still exists on stage<br />
- A stage child is orphaned if its parent was deleted from stage, but still exists on live
</p>
	";

    protected $orphanedSearchClass = SiteTree::class;

    protected function init()
    {
        parent::init();

        if (!Permission::check('ADMIN')) {
            Security::permissionFailure($this);
        }
    }

    public function Link($action = null)
    {
        /** @skipUpgrade */
        return Controller::join_links('RemoveOrphanedPagesTask', $action, '/');
    }

    public function index()
    {
        Requirements::javascript('http://code.jquery.com/jquery-1.7.2.min.js');
        Requirements::customCSS('#OrphanIDs .middleColumn {width: auto;}');
        Requirements::customCSS('#OrphanIDs label {display: inline;}');

        return $this->renderWith('BlankPage');
    }

    public function Form()
    {
        $fields = new FieldList();
        $source = array();

        $fields->push(new HeaderField(
            'Header',
            _t('SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.HEADER', 'Remove all orphaned pages task')
        ));
        $fields->push(new LiteralField(
            'Description',
            $this->description
        ));

        $orphans = $this->getOrphanedPages($this->orphanedSearchClass);
        if ($orphans) {
            foreach ($orphans as $orphan) {
                        /** @var SiteTree $latestVersion */
                $latestVersion = Versioned::get_latest_version($this->orphanedSearchClass, $orphan->ID);
                $latestAuthor = DataObject::get_by_id('SilverStripe\\Security\\Member', $latestVersion->AuthorID);
                $orphanBaseTable = DataObject::getSchema()->baseDataTable($this->orphanedSearchClass);
                $liveRecord = Versioned::get_one_by_stage(
                    $this->orphanedSearchClass,
                    'Live',
                    array("\"$orphanBaseTable\".\"ID\"" => $orphan->ID)
                );
                            $label = sprintf(
                                '<a href="admin/pages/edit/show/%d">%s</a> <small>(#%d, Last Modified Date: %s, Last Modifier: %s, %s)</small>',
                                $orphan->ID,
                                $orphan->Title,
                                $orphan->ID,
                                $orphan->dbObject('LastEdited')->Nice(),
                                ($latestAuthor) ? $latestAuthor->Title : 'unknown',
                                ($liveRecord) ? 'is published' : 'not published'
                            );
                            $source[$orphan->ID] = $label;
            }
        }

        if ($orphans && $orphans->count()) {
            $fields->push(new CheckboxSetField('OrphanIDs', false, $source));
            $fields->push(new LiteralField(
                'SelectAllLiteral',
                sprintf(
                    '<p><a href="#" onclick="javascript:jQuery(\'#Form_Form_OrphanIDs :checkbox\').attr(\'checked\', \'checked\'); return false;">%s</a>&nbsp;',
                    _t('SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.SELECTALL', 'select all')
                )
            ));
            $fields->push(new LiteralField(
                'UnselectAllLiteral',
                sprintf(
                    '<a href="#" onclick="javascript:jQuery(\'#Form_Form_OrphanIDs :checkbox\').attr(\'checked\', \'\'); return false;">%s</a></p>',
                    _t('SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.UNSELECTALL', 'unselect all')
                )
            ));
            $fields->push(new OptionsetField(
                'OrphanOperation',
                _t('SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.CHOOSEOPERATION', 'Choose operation:'),
                array(
                    'rebase' => _t(
                        'SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.OPERATION_REBASE',
                        sprintf(
                            'Rebase selected to a new holder page "%s" and unpublish. None of these pages will show up for website visitors.',
                            $this->rebaseHolderTitle()
                        )
                    ),
                    'remove' => _t('SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.OPERATION_REMOVE', 'Remove selected from all stages (WARNING: Will destroy all selected pages from both stage and live)'),
                ),
                'rebase'
            ));
            $fields->push(new LiteralField(
                'Warning',
                sprintf(
                    '<p class="message">%s</p>',
                    _t(
                        'SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.DELETEWARNING',
                        'Warning: These operations are not reversible. Please handle with care.'
                    )
                )
            ));
        } else {
            $fields->push(new LiteralField(
                'NotFoundLabel',
                sprintf(
                    '<p class="message">%s</p>',
                    _t('SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.NONEFOUND', 'No orphans found')
                )
            ));
        }

        $form = new Form(
            $this,
            'SilverStripe\\Forms\\Form',
            $fields,
            new FieldList(
                new FormAction('doSubmit', _t('SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.BUTTONRUN', 'Run'))
            )
        );

        if (!$orphans || !$orphans->count()) {
            $form->makeReadonly();
        }

        return $form;
    }

    public function run($request)
    {
        // @todo Merge with BuildTask functionality
    }

    public function doSubmit($data, $form)
    {
        set_time_limit(60*10); // 10 minutes

        if (!isset($data['OrphanIDs']) || !isset($data['OrphanOperation'])) {
            return false;
        }

        $successIDs = null;
        switch ($data['OrphanOperation']) {
            case 'remove':
                $successIDs = $this->removeOrphans($data['OrphanIDs']);
                break;
            case 'rebase':
                $successIDs = $this->rebaseOrphans($data['OrphanIDs']);
                break;
            default:
                user_error(sprintf("Unknown operation: '%s'", $data['OrphanOperation']), E_USER_ERROR);
        }

        $content = '';
        if ($successIDs) {
            $content .= "<ul>";
            foreach ($successIDs as $id => $label) {
                $content .= sprintf('<li>%s</li>', $label);
            }
            $content .= "</ul>";
        } else {
            $content = _t('SilverStripe\\CMS\\Tasks\\RemoveOrphanedPagesTask.NONEREMOVED', 'None removed');
        }

        return $this->customise(array(
            'Content' => $content,
            'Form' => ' '
        ))->renderWith('BlankPage');
    }

    protected function removeOrphans($orphanIDs)
    {
        $removedOrphans = array();
        $orphanBaseTable = DataObject::getSchema()->baseDataTable($this->orphanedSearchClass);
        foreach ($orphanIDs as $id) {
            /** @var SiteTree $stageRecord */
            $stageRecord = Versioned::get_one_by_stage(
                $this->orphanedSearchClass,
                Versioned::DRAFT,
                array("\"$orphanBaseTable\".\"ID\"" => $id)
            );
            if ($stageRecord) {
                $removedOrphans[$stageRecord->ID] = sprintf('Removed %s (#%d) from Stage', $stageRecord->Title, $stageRecord->ID);
                $stageRecord->delete();
                $stageRecord->destroy();
                unset($stageRecord);
            }
            /** @var SiteTree $liveRecord */
            $liveRecord = Versioned::get_one_by_stage(
                $this->orphanedSearchClass,
                Versioned::LIVE,
                array("\"$orphanBaseTable\".\"ID\"" => $id)
            );
            if ($liveRecord) {
                $removedOrphans[$liveRecord->ID] = sprintf('Removed %s (#%d) from Live', $liveRecord->Title, $liveRecord->ID);
                $liveRecord->doUnpublish();
                $liveRecord->destroy();
                unset($liveRecord);
            }
        }

        return $removedOrphans;
    }

    protected function rebaseHolderTitle()
    {
        return sprintf('Rebased Orphans (%s)', date('d/m/Y g:ia', time()));
    }

    protected function rebaseOrphans($orphanIDs)
    {
        $holder = new SiteTree();
        $holder->ShowInMenus = 0;
        $holder->ShowInSearch = 0;
        $holder->ParentID = 0;
        $holder->Title = $this->rebaseHolderTitle();
        $holder->write();

        $removedOrphans = array();
        $orphanBaseTable = DataObject::getSchema()->baseDataTable($this->orphanedSearchClass);
        foreach ($orphanIDs as $id) {
            /** @var SiteTree $stageRecord */
            $stageRecord = Versioned::get_one_by_stage(
                $this->orphanedSearchClass,
                'Stage',
                array("\"$orphanBaseTable\".\"ID\"" => $id)
            );
            if ($stageRecord) {
                $removedOrphans[$stageRecord->ID] = sprintf('Rebased %s (#%d)', $stageRecord->Title, $stageRecord->ID);
                $stageRecord->ParentID = $holder->ID;
                $stageRecord->ShowInMenus = 0;
                $stageRecord->ShowInSearch = 0;
                $stageRecord->write();
                $stageRecord->doUnpublish();
                $stageRecord->destroy();
                //unset($stageRecord);
            }
            /** @var SiteTree $liveRecord */
            $liveRecord = Versioned::get_one_by_stage(
                $this->orphanedSearchClass,
                'Live',
                array("\"$orphanBaseTable\".\"ID\"" => $id)
            );
            if ($liveRecord) {
                $removedOrphans[$liveRecord->ID] = sprintf('Rebased %s (#%d)', $liveRecord->Title, $liveRecord->ID);
                $liveRecord->ParentID = $holder->ID;
                $liveRecord->ShowInMenus = 0;
                $liveRecord->ShowInSearch = 0;
                $liveRecord->write();
                if (!$stageRecord) {
                    $liveRecord->doRestoreToStage();
                }
                $liveRecord->doUnpublish();
                $liveRecord->destroy();
                unset($liveRecord);
            }
            if ($stageRecord) {
                unset($stageRecord);
            }
        }

        return $removedOrphans;
    }

    /**
     * Gets all orphans from "Stage" and "Live" stages.
     *
     * @param string $class
     * @param array $filter
     * @param string $sort
     * @param string $join
     * @param int|array $limit
     * @return SS_List
     */
    public function getOrphanedPages($class = SiteTree::class, $filter = array(), $sort = null, $join = null, $limit = null)
    {
        // Alter condition
        $table = DataObject::getSchema()->tableName($class);
        if (empty($filter)) {
            $where = array();
        } elseif (is_array($filter)) {
            $where = $filter;
        } else {
            $where = array($filter);
        }
        $where[] = array("\"{$table}\".\"ParentID\" != ?" => 0);
        $where[] = '"Parents"."ID" IS NULL';

        $orphans = new ArrayList();
        foreach (array(Versioned::DRAFT, Versioned::LIVE) as $stage) {
            $table .= ($stage == Versioned::LIVE) ? '_Live' : '';
            $stageOrphans = Versioned::get_by_stage(
                $class,
                $stage,
                $where,
                $sort,
                null,
                $limit
            )->leftJoin($table, "\"$table\".\"ParentID\" = \"Parents\".\"ID\"", "Parents");
            $orphans->merge($stageOrphans);
        }

        $orphans->removeDuplicates();

        return $orphans;
    }
}
