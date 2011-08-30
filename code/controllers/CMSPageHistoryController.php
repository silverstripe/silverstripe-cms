<?php

/**
 * @package cms
 * @subpackage controllers
 */
class CMSPageHistoryController extends CMSMain {

	static $url_segment = 'page/history';
	static $url_rule = '/$Action/$ID/$VersionID/$OtherVersionID';
	static $url_priority = 42;
	static $menu_title = 'History';
	
	static $allowed_actions = array(
		'VersionsForm',
		'compare'
	);
	
	public static $url_handlers = array(
		'$Action/$ID/$VersionID/$OtherVersionID' => 'handleAction'
	);
	
	/**
	 * @return array
	 */
	function show() {
		return array(
			'EditForm' => $this->ShowVersionForm(
				$this->request->param('VersionID')
			)
		);
	}
	
	/**
	 * @return array
	 */
	function compare() {
		return array(
			'EditForm' => $this->CompareVersionsForm(
				$this->request->param('VersionID'), 
				$this->request->param('OtherVersionID')
			)
		);
	}
	
	/**
	 * @return array
	 */
	function rollback() {
		return $this->doRollback(array(
			'ID' => $this->currentPageID(),
			'Version' => $this->request->param('VersionID')
		), null);
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
	 * @param int $compare Compare mode
	 *
	 * @return Form
	 */
	function getEditForm($id = null, $fields = null, $versionID = null, $compareID = null) {
		if(!$id) $id = $this->currentPageID();
		
		$record = $this->getRecord($id, $versionID);
		$versionID = ($record) ? $record->Version : $versionID;
		
		$form = parent::getEditForm($record, ($record) ? $record->getCMSFields() : null);

		$form->setActions(new FieldSet(
			$revert = new FormAction('doRollback', _t('CMSPageHistoryController.REVERTTOTHISVERSION', 'Revert to this version'))
		));
		
		$fields = $form->Fields();
		$fields->removeByName("Status");
		$fields->push(new HiddenField("ID"));
		$fields->push(new HiddenField("Version"));
		
		$fields = $fields->makeReadonly();

		foreach($fields->dataFields() as $field) {
			$field->dontEscape = true;
			$field->reserveNL = true;
		}
		
		if($compareID) {
			$link = Controller::join_links(
				$this->Link('version'),
				$id
			);

			$view = _t('CMSPageHistoryController.VIEW',"view");
			
			$message = sprintf(
				_t('CMSPageHistoryController.COMPARINGVERSION',"Comparing versions %s and %s."),
				sprintf('%s (<a href="%s">%s</a>)', $versionID, Controller::join_links($link, $versionID), $view),
				sprintf('%s (<a href="%s">%s</a>)', $compareID, Controller::join_links($link, $compareID), $view)
			);
			
			$revert->setReadonly(true);
		}
		else {
			$message = sprintf(
				_t('CMSPageHistoryController.VIEWINGVERSION',"Currently viewing version %s."), $versionID
			);
		}
		
		$fields->addFieldToTab('Root.Main', 
			new LiteralField('CurrentlyViewingMessage', $this->customise(array(
				'Content' => $message,
				'Classes' => 'notice'
			))->renderWith(array('CMSMain_notice'))),
			"Title"
		);

		$form->setFields($fields->makeReadonly());
		$form->loadDataFrom(array(
			"ID" => $id,
			"Version" => $versionID,
		));
		
		if(($record && $record->isLatestVersion())) {
			$revert->setReadonly(true);
		}
		
		$form->removeExtraClass('cms-content');

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
	function VersionsForm() {
		$id = $this->currentPageID();
		$page = $this->getRecord($id);
		$versionsHtml = '';

		$action = $this->request->param('Action');
		$versionID = $this->request->param('VersionID');
		$otherVersionID = $this->request->param('OtherVersionID');
		
		$showUnpublishedChecked = 0;
		$compareModeChecked = ($action == "compare");

		if($page) {
			$versions = $page->allVersions();
			$versionID = (!$versionID) ? $page->Version : $versionID;

			if($versions) {
				foreach($versions as $k => $version) {
					$active = false;
					
					if($version->Version == $versionID || $version->Version == $otherVersionID) {
						$active = true;
						
						if(!$version->WasPublished) $showUnpublishedChecked = 1;
					}

					$version->Active = ($active);
				}
			}
			
			$vd = new ViewableData();
			
			$versionsHtml = $vd->customise(array(
				'Versions' => $versions
			))->renderWith('CMSPageHistoryController_versions');
		}

		$form = new Form(
			$this,
			'VersionsForm',
			new FieldSet(
				new CheckboxField(
					'ShowUnpublished',
					_t('CMSPageHistoryController.SHOWUNPUBLISHED','Show unpublished versions'),
					$showUnpublishedChecked
				),
				new CheckboxField(
					'CompareMode',
					_t('CMSPageHistoryController.COMPAREMODE', 'Compare mode'),
					$compareModeChecked
				),
				new LiteralField('VersionsHtml', $versionsHtml),
				$hiddenID = new HiddenField('ID', false, "")
			),
			new FieldSet(
				new FormAction(
					'doCompare', _t('CMSPageHistoryController.COMPAREVERSIONS','Compare Versions')
				),
				new FormAction(
					'doShowVersion', _t('CMSPageHistoryController.SHOWVERSION','Show Version') 
				)
			)
		);
		
		$form->loadDataFrom($this->request->requestVars());
		$hiddenID->setValue($id);
		$form->unsetValidator();
		
		return $form;
	}
	
	/**
	 * Process the {@link VersionsForm} compare function between two pages.
	 *
	 * @param array
	 * @param Form
	 *
	 * @return html
	 */
	function doCompare($data, $form) {
		$versions = $data['Versions'];
		if(count($versions) < 2) return null;
		
		$id = $this->currentPageID();
		$version1 = array_shift($versions);
		$version2 = array_shift($versions);

		$form = $this->CompareVersionsForm($version1, $version2);

		// javascript solution, render into template
		if($this->isAjax()) {
			return $this->customise(array(
				"EditForm" => $form
			))->renderWith(array(
				$this->class . '_EditForm', 
				'LeftAndMain_Content'
			));
		}
		
		// non javascript, redirect the user to the page
		$this->redirect(Controller::join_links(
			$this->Link('compare'),
			$version1,
			$version2
		));
	}

	/**
	 * Process the {@link VersionsForm} show version function. Only requires
	 * one page to be selected.
	 *
	 * @param array
	 * @param Form
	 *
	 * @return html
	 */	
	function doShowVersion($data, $form) {
		$versionID = null;
		
		if(isset($data['Versions']) && is_array($data['Versions'])) { 
			$versionID  = array_shift($data['Versions']);
		}
		
		if(!$versionID) return;
		
		if($this->isAjax()) {
			return $this->customise(array(
				"EditForm" => $this->ShowVersionForm($versionID)
			))->renderWith(array(
				$this->class . '_EditForm', 
				'LeftAndMain_Content'
			));
		}

		// non javascript, redirect the user to the page
		$this->redirect(Controller::join_links(
			$this->Link('version'),
			$versionID
		));
	}
	
	/**
	 * Rolls a site back to a given version ID
	 *
	 * @param array
	 * @param Form
	 *
	 * @return html
	 */
	function doRollback($data, $form) {
		$this->extend('onBeforeRollback', $data['ID']);
		
		$id = (isset($data['ID'])) ? (int) $data['ID'] : null;
		$version = (isset($data['Version'])) ? (int) $data['Version'] : null;
		
		if(isset($data['Version']) && (bool)$data['Version']) {
			$record = $this->performRollback($data['ID'], $data['Version']);
			$message = sprintf(
			_t('CMSMain.ROLLEDBACKVERSION',"Rolled back to version #%d.  New version number is #%d"),
			$data['Version'],
			$record->Version
		);
		} else {
			$record = $this->performRollback($data['ID'], "Live");
			$message = sprintf(
				_t('CMSMain.ROLLEDBACKPUB',"Rolled back to published version. New version number is #%d"),
				$record->Version
			);
		}
		
		if($this->isAjax()) {
			$this->response->addHeader('X-Status', $message);
			$form = $this->getEditForm($record->ID);
		
			return $form->forTemplate();
		}

		return array(
			'EditForm' => $this->customise(array(
				'Message' => $message,
				'Status' => 'success'
			))->renderWith('CMSMain_notice')
		);
	}
	
	/**
	 * Performs a rollback of the a given 
	 *
	 * @param int $id record ID
	 * @param int $version version ID to rollback to
	 */
	function performRollback($id, $version) {
		$record = DataObject::get_by_id($this->stat('tree_class'), $id);
		
		if($record && !$record->canEdit()) return Security::permissionFailure($this);
		
		$record->doRollbackTo($version);
		
		return $record;
	}

	/**
	 * @return Form
	 */
	function ShowVersionForm($versionID = null) {
		if(!$versionID) return null;

		$id = $this->currentPageID();
		$form = $this->getEditForm($id, null, $versionID);

		return $form;
	}
	
	/**
	 * @return Form
	 */
	function CompareVersionsForm($versionID, $otherVersionID) {
		if($versionID > $otherVersionID) {
			$toVersion = $versionID;
			$fromVersion = $otherVersionID;
		} else {
			$toVersion = $otherVersionID;
			$fromVersion = $versionID;
		}

		if(!$toVersion || !$toVersion) return false;
		
		$id = $this->currentPageID();
		$page = DataObject::get_by_id("SiteTree", $id);
		
		if($page && !$page->canView()) {
			return Security::permissionFailure($this);
		}

		$record = $page->compareVersions($fromVersion, $toVersion);

		$fromVersionRecord = Versioned::get_version('SiteTree', $id, $fromVersion);
		$toVersionRecord = Versioned::get_version('SiteTree', $id, $toVersion);
		
		if(!$fromVersionRecord) {
			user_error("Can't find version $fromVersion of page $id", E_USER_ERROR);
		}
		
		if(!$toVersionRecord) {
			user_error("Can't find version $toVersion of page $id", E_USER_ERROR);
		}

		if($record) {
			$form = $this->getEditForm($id, null, null, true);
			$form->setActions(new FieldSet());
			$form->loadDataFrom($record);
			
			$form->loadDataFrom(array(
				"ID" => $id,
				"Version" => $fromVersion,
			));
			
			$form->addExtraClass('compare');
			
			return $form;
		}
	}
}