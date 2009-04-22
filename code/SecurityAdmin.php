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
		'addgroup',
		'addmember',
		'autocomplete',
		'removememberfromgroup',
		'savemember',
		'AddRecordForm',
		'MemberForm',
		'EditForm'
	);

	public function init() {
		parent::init();

		Requirements::javascript(THIRDPARTY_DIR . "/hover.js");
		Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/controls.js");

		// needed for MemberTableField (Requirements not determined before Ajax-Call)
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/TableListField.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/TableField.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/ComplexTableField.js");
		Requirements::javascript(CMS_DIR . "/javascript/MemberTableField.js");
		Requirements::css(THIRDPARTY_DIR . "/greybox/greybox.css");
		Requirements::css(SAPPHIRE_DIR . "/css/ComplexTableField.css");

		Requirements::javascript(CMS_DIR . '/javascript/SecurityAdmin_left.js');
		Requirements::javascript(CMS_DIR . '/javascript/SecurityAdmin_right.js');
		
		Requirements::javascript(THIRDPARTY_DIR . "/greybox/AmiJS.js");
		Requirements::javascript(THIRDPARTY_DIR . "/greybox/greybox.js");
	}

	public function getEditForm($id) {
		$record = null;
		
		if($id && $id != 'root') {
			$record = DataObject::get_by_id($this->stat('tree_class'), $id);
		}
		
		if(!$record) return false;
		
		$fields = $record->getCMSFields();
		
		$actions = new FieldSet(
			new FormAction('addmember',_t('SecurityAdmin.ADDMEMBER','Add Member')),
			new FormAction('save',_t('SecurityAdmin.SAVE','Save'))
		);

		$form = new Form($this, "EditForm", $fields, $actions);
		$form->loadDataFrom($record);
		
		return $form;
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
		if(!array_key_exists($fieldName, singleton($this->stat('subitem_class'))->stat('db')) || $fieldName == 'Password') return;

		$matches = DataObject::get($this->stat('subitem_class'),"$fieldName LIKE '" . Convert::raw2sql($fieldVal) . "%'");
		if($matches) {
			$result .= "<ul>";
			foreach($matches as $match) {

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
			'"<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" >" . $child->TreeTitle() . "</a>" ',
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

	public function addgroup() {
		$newGroup = Object::create($this->stat('tree_class'));
		$newGroup->Title = _t('SecurityAdmin.NEWGROUP',"New Group");
		$newGroup->Code = "new-group";
		$newGroup->ParentID = (is_numeric($_REQUEST['ParentID'])) ? (int)$_REQUEST['ParentID'] : 0;
		$newGroup->write();
		
		return $this->returnItemToUser($newGroup);
	}

	public function EditedMember() {
		if(Session::get('currentMember')) return DataObject::get_by_id('Member', (int) Session::get('currentMember'));
	}

	function providePermissions() {
		return array(
			'EDIT_PERMISSIONS' => _t('SecurityAdmin.EDITPERMISSIONS', 'Edit permissions and IP addresses on each group'),
		);
	}
}

?>
