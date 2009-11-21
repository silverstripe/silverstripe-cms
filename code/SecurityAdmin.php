<?php
/**
 * Security section of the CMS
 * @package cms
 * @subpackage security
 */
class SecurityAdmin extends LeftAndMain implements PermissionProvider {

	static $url_segment = 'security';
	
	static $url_rule = '/$Action/$ID/$OtherID';
	
	static $menu_title = 'Security';
	
	static $tree_class = 'Group';
	
	static $subitem_class = 'Member';
	
	static $allowed_actions = array(
		'addmember',
		'autocomplete',
		'removememberfromgroup',
		'savemember',
		'AddForm',
		'AddRecordForm',
		'MemberForm',
		'EditForm',
	);

	public function init() {
		parent::init();

		Requirements::javascript(CMS_DIR . '/javascript/SecurityAdmin.js');
		Requirements::javascript(CMS_DIR . '/javascript/SecurityAdmin.Tree.js');
				
		CMSBatchActionHandler::register('delete', 'SecurityAdmin_DeleteBatchAction', 'Group');
	}
	
	function getEditForm($id = null) {
		$form = parent::getEditForm($id);
		$form->Actions()->insertBefore(
			new FormAction('addmember',_t('SecurityAdmin.ADDMEMBER','Add Member')),
			'action_save'
		);
		
		return $form;
	}
	
	/**
	 * @return Form
	 */
	function AddForm() {
		$class = $this->stat('tree_class');
		
		$typeMap = array('Folder' => singleton($class)->i18n_singular_name());
		$typeField = new DropdownField('Type', false, $typeMap, 'Folder');
		$form = new Form(
			$this,
			'AddForm',
			new FieldSet(
				new HiddenField('ParentID'),
				$typeField->performReadonlyTransformation()
			),
			new FieldSet(
				new FormAction('doAdd', _t('AssetAdmin_left.ss.GO','Go'))
			)
		);
		$form->setValidator(null);
		$form->addExtraClass('actionparams');
		
		return $form;
	}
	
	/**
	 * Add a new group and return its details suitable for ajax.
	 */
	public function doAdd($data, $form) {
		$parentID = (isset($data['ParentID']) && is_numeric($data['ParentID'])) ? (int)$data['ParentID'] : 0;
		
		if(!singleton($this->stat('tree_class'))->canCreate()) return Security::permissionFailure($this);
		
		$record = Object::create($this->stat('tree_class'));
		$record->Title = _t('SecurityAdmin.NEWGROUP',"New Group");
		$record->ParentID = $parentID;
		$record->write();

		$form = $this->getEditForm($record->ID);
		return $form->formHtmlContent();
	}

	public function AddRecordForm() {
		$m = Object::create('MemberTableField',
			$this,
			"Members",
			$this->currentPageID()
		);
		return $m->AddRecordForm();
	}

	/**
	 * Ajax autocompletion
	 */
	public function autocomplete() {
		$fieldName = $this->urlParams['ID'];
		$fieldVal = $_REQUEST[$fieldName];
		$result = '';

		// Make sure we only autocomplete on keys that actually exist, and that we don't autocomplete on password
		if(!singleton($this->stat('subitem_class'))->hasDatabaseField($fieldName)  || $fieldName == 'Password') return;

		$matches = DataObject::get($this->stat('subitem_class'),"\"$fieldName\" LIKE '" . Convert::raw2sql($fieldVal) . "%'");
		if($matches) {
			$result .= "<ul>";
			foreach($matches as $match) {
				if(!$match->canView()) continue;

				$data = $match->FirstName;
				$data .= ",$match->Surname";
				$data .= ",$match->Email";
				$result .= "<li>" . $match->$fieldName . "<span class=\"informal\">($match->FirstName $match->Surname, $match->Email)</span><span class=\"informal data\">$data</span></li>";
			}
			$result .= "</ul>";
			return $result;
		}
	}

	public function MemberForm() {
		$id = $_REQUEST['ID'] ? $_REQUEST['ID'] : Session::get('currentMember');
		if($id) return $this->getMemberForm($id);
	}

	public function getMemberForm($id) {
		if($id && $id != 'new') $record = DataObject::get_by_id('Member', (int) $id);
		if($record || $id == 'new') {
			$fields = new FieldSet(
				new HiddenField('MemberListBaseGroup', '', $this->currentPageID() )
			);

			if($extraFields = $record->getCMSFields()) {
				foreach($extraFields as $extra) {
					$fields->push( $extra );
				}
			}

			$fields->push($idField = new HiddenField('ID'));
			$fields->push($groupIDField = new HiddenField('GroupID'));

			$actions = new FieldSet();
			$actions->push(new FormAction('savemember', _t('SecurityAdmin.SAVE')));

			$form = new Form($this, 'MemberForm', $fields, $actions);
			if($record) $form->loadDataFrom($record);

			$idField->setValue($id);
			$groupIDField->setValue($this->currentPageID());
			
			if($record && !$record->canEdit()) {
				$readonlyFields = $form->Fields()->makeReadonly();
				$form->setFields($readonlyFields);
			}

			return $form;
		}
	}

	function savemember() {
		$data = $_REQUEST;
		$className = $this->stat('subitem_class');

		$id = $_REQUEST['ID'];
		if($id == 'new') $id = null;

		if($id) {
			$record = DataObject::get_by_id($className, $id);
			if($record && !$record->canEdit()) return Security::permissionFailure($this);
		} else {
			if(!singleton($this->stat('subitem_class'))->canCreate()) return Security::permissionFailure($this);
			$record = new $className();
		}

		$record->update($data);
		$record->ID = $id;
		$record->write();

		$record->Groups()->add($data['GroupID']);

		FormResponse::add("reloadMemberTableField();");

		return FormResponse::respond();
	}

	function addmember($className=null) {
		$data = $_REQUEST;
		unset($data['ID']);
		if($className == null) $className = $this->stat('subitem_class');

		if(!singleton($this->stat('subitem_class'))->canCreate()) return Security::permissionFailure($this);

		$record = new $className();

		$record->update($data);
		$record->write();
		
		if($data['GroupID']) $record->Groups()->add((int)$data['GroupID']);

		FormResponse::add("reloadMemberTableField();");

		return FormResponse::respond();
	}

	public function removememberfromgroup() {
		$groupID = $this->urlParams['ID'];
		$memberID = $this->urlParams['OtherID'];
		if(is_numeric($groupID) && is_numeric($memberID)) {
			$member = DataObject::get_by_id('Member', (int) $memberID);

			if(!$member->canDelete()) return Security::permissionFailure($this);

			$member->Groups()->remove((int)$groupID);

			FormResponse::add("reloadMemberTableField();");
		} else {
			user_error("SecurityAdmin::removememberfromgroup: Bad parameters: Group=$groupID, Member=$memberID", E_USER_ERROR);
		}

		return FormResponse::respond();
	}
	
	/**
	 * Return the entire site tree as a nested set of ULs.
	 * @return string Unordered list <UL> HTML
	 */
	public function SiteTreeAsUL() {
		$obj = singleton($this->stat('tree_class'));
		$obj->markPartialTree();
		
		if($p = $this->currentPage()) $obj->markToExpose($p);

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$siteTreeList = $obj->getChildrenAsUL(
			'',
			'"<li id=\"record-$child->ID\" class=\"$child->class " . ($child->Locked ? " nodelete" : "") . $child->markingClasses() . ($extraArg->isCurrentPage($child) ? " current" : "") . "\">" . ' .
			'"<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" >" . $child->TreeTitle . "</a>" ',
			$this,
			true
		);	

		// Wrap the root if needs be
		$rootLink = $this->Link() . 'show/root';
		$rootTitle = _t('SecurityAdmin.SGROUPS', 'Security Groups');
		if(!isset($rootID)) {
			$siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-root\" class=\"Root\"><a href=\"$rootLink\"><strong>{$rootTitle}</strong></a>"
			. $siteTreeList . "</li></ul>";
		}
							
		return $siteTree;
	}

	public function EditedMember() {
		if(Session::get('currentMember')) return DataObject::get_by_id('Member', (int) Session::get('currentMember'));
	}

	function providePermissions() {
		return array(
			'EDIT_PERMISSIONS' => array(
				'name' => _t('SecurityAdmin.EDITPERMISSIONS', 'Manage permissions for groups'),
				'category' => _t('Permissions.PERMISSIONS_CATEGORY', 'Roles and access permissions'),
				'help' => _t('SecurityAdmin.EDITPERMISSIONS_HELP', 'Ability to edit Permissions and IP Addresses for a group. Requires "Access to Security".'),
				'sort' => 0
			),
			'APPLY_ROLES' => array(
				'name' => _t('SecurityAdmin.APPLY_ROLES', 'Apply roles to groups'),
				'category' => _t('Permissions.PERMISSIONS_CATEGORY', 'Roles and access permissions'),
				'help' => _t('SecurityAdmin.APPLY_ROLES_HELP', 'Ability to edit the roles assigned to a group. Requires "Access to Security.".'),
				'sort' => 0
			)
		);
	}
	
	/**
	 * the permissions represented in the $codes will not appearing in the form
	 * containning {@link PermissionCheckboxSetField} so as not to be checked / unchecked.
	 * @param $codes array of permission code
	 * @return void
	 */
	static function hide_permissions($codes){
		foreach($codes as $code){
			Permission::add_to_hidden_permissions($code);
		}
	}
}

/**
 * Delete multiple {@link Group} records. Usually used through the {@link SecurityAdmin} interface.
 * 
 * @package cms
 * @subpackage batchactions
 */
class SecurityAdmin_DeleteBatchAction extends CMSBatchAction {
	function getActionTitle() {
		return _t('AssetAdmin_DeleteBatchAction.TITLE', 'Delete groups');
	}

	function run(DataObjectSet $records) {
		$status = array(
			'modified'=>array(),
			'deleted'=>array()
		);
		
		foreach($records as $record) {
			// TODO Provide better feedback if permission was denied
			if(!$record->canDelete()) continue;
			
			$id = $record->ID;
			$record->delete();
			$status['deleted'][$id] = array();
			$record->destroy();
			unset($record);
		}

		return Convert::raw2json($status);
	}
}
?>
