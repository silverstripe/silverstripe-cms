<?php
/**
 * Security section of the CMS
 * @package cms
 * @subpackage security
 */
class SecurityAdmin extends LeftAndMain implements PermissionProvider {
	static $tree_class = "Group";
	static $subitem_class = "Member";
	
	static $allowed_actions = array(
		'addgroup',
		'addmember',
		'autocomplete',
		'getmember',
		'listmembers',
		'newmember',
		'removememberfromgroup',
		'savemember',
		'AddRecordForm',
		'MemberForm'
	);

	public function init() {
		// Check permissions
		// if(!Member::currentUser() || !Member::currentUser()->isAdmin()) Security::permissionFailure($this);

		parent::init();

		Requirements::javascript("jsparty/hover.js");
		Requirements::javascript("jsparty/scriptaculous/controls.js");

		// needed for MemberTableField (Requirements not determined before Ajax-Call)
		Requirements::javascript("sapphire/javascript/TableListField.js");
		Requirements::javascript("sapphire/javascript/TableField.js");
		Requirements::javascript("sapphire/javascript/ComplexTableField.js");
		Requirements::javascript("cms/javascript/MemberTableField.js");
		Requirements::css("jsparty/greybox/greybox.css");
		Requirements::css("sapphire/css/ComplexTableField.css");

		Requirements::javascript("cms/javascript/SecurityAdmin.js");
		Requirements::javascript("cms/javascript/SecurityAdmin_left.js");
		Requirements::javascript("cms/javascript/SecurityAdmin_right.js");

		Requirements::javascript("jsparty/greybox/AmiJS.js");
		Requirements::javascript("jsparty/greybox/greybox.js");
	}

	public function getEditForm($id) {
		$record = DataObject::get_by_id($this->stat('tree_class'), $id);
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
		$m = new MemberTableField(
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

		$matches = DataObject::get("Member","$fieldName LIKE '" . addslashes($fieldVal) . "%'");
		if($matches) {
			$result .= "<ul>";
			foreach($matches as $match) {

				$data = $match->FirstName;
				$data .= ",$match->Surname";
				$data .= ",$match->Email";
				$data .= ",$match->Password";
				$result .= "<li>" . $match->$fieldName . "<span class=\"informal\">($match->FirstName $match->Surname, $match->Email)</span><span class=\"informal data\">$data</span></li>";
			}
			$result .= "</ul>";
			return $result;
		}
	}

	public function getmember() {
		Session::set('currentMember', $_REQUEST['ID']);
		SSViewer::setOption('rewriteHashlinks', false);
		$result = $this->renderWith("LeftAndMain_rightbottom");
		$parts = split('</?form[^>]*>', $result);
		echo $parts[1];
	}


	public function MemberForm() {
		$id = $_REQUEST['ID'] ? $_REQUEST['ID'] : Session::get('currentMember');
		if($id)
			return $this->getMemberForm($id);
	}

	public function getMemberForm($id) {
		if($id && $id != 'new') $record = DataObject::get_one("Member", "`Member`.ID = $id");
		if($record || $id == 'new') {
			$fields = new FieldSet(
				new HiddenField('MemberListBaseGroup', '', $this->currentPageID() )
			);

			if( $extraFields = $record->getCMSFields() )
				foreach( $extraFields as $extra )
					$fields->push( $extra );

			$fields->push($idField = new HiddenField("ID"));
			$fields->push($groupIDField = new HiddenField("GroupID"));


			$actions = new FieldSet();
			$actions->push(new FormAction('savemember',_t('SecurityAdmin.SAVE')));

			$form = new Form($this, "MemberForm", $fields, $actions);
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
			$record = DataObject::get_one($className, "`$className`.ID = $id");
		} else {
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
		if($className == null)
			$className = $this->stat('subitem_class');
		$record = new $className();

		$record->update($data);
		$record->write();
		if($data['GroupID'])
			$record->Groups()->add($data['GroupID']);

		FormResponse::add("reloadMemberTableField();");

		return FormResponse::respond();
	}

	public function removememberfromgroup() {
		$groupID = $this->urlParams['ID'];
		$memberID = $this->urlParams['OtherID'];
		if(is_numeric($groupID) && is_numeric($memberID)) {
			$member = DataObject::get_by_id('Member', $memberID);
			$member->Groups()->remove($groupID);
			FormResponse::add("reloadMemberTableField();");

		} else {
			user_error("SecurityAdmin::removememberfromgroup: Bad parameters: Group=$groupID, Member=$memberID", E_USER_ERROR);
		}

		return FormResponse::respond();
	}

	/**
	 * Return the entire site tree as a nested set of ULs
	 */
	public function SiteTreeAsUL() {
		$obj = singleton($this->stat('tree_class'));

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$siteTree = $obj->getChildrenAsUL("",
			' "<li id=\"record-$child->ID\" class=\"$child->class " . ($child->Locked ? " nodelete" : "") . ' .
			' ($extraArg->isCurrentPage($child) ? " current" : "") . "\">" . ' .
			' "<a href=\"" . Director::link("admin", "show", $child->ID) . "\" >" . $child->TreeTitle() . "</a>" ',$this);

		$siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\">" .
						"<li id=\"record-0\" class=\"Root\">" .
							"<a href=\"admin/security/show/0\" ><strong>"._t('SecurityAdmin.SGROUPS',"Security groups")."</strong></a>"
							. $siteTree .
						"</li>" .
					"</ul>";

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

	public function newmember() {
		Session::clear('currentMember');
		$newMemberForm = array(
			"MemberForm" => $this->getMemberForm('new'),
		);
		// This should be using FormResponse ;-)
		if(Director::is_ajax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			$customised = $this->customise($newMemberForm);
			$result = $customised->renderWith($this->class . "_rightbottom");
			$parts = split('</?form[^>]*>', $result);
			return $parts[1];

		} else {
			return $newMemberForm;
		}
	}

	public function EditedMember() {
		if(Session::get('currentMember'))
			return DataObject::get_by_id("Member", Session::get('currentMember'));
	}

	public function Link($action = null) {
		if(!$action) $action = "index";
		return "admin/security/$action/" . $this->currentPageID();
	}

	public function listmembers( $baseGroup = null ) {

		if( !$baseGroup )
			$baseGroup = $this->urlParams['ID'];

		// Debug::message( $_REQUEST['MemberListOrderByField'] );

		// construct the filter and sort

		if( $_REQUEST['MemberListOrderByField'] )
			$sort = "`" . $_REQUEST['MemberListOrderByField'] . "`" . addslashes( $_REQUEST['MemberListOrderByOrder'] );

		$whereClauses = array();

		$search = addslashes( $_REQUEST['MemberListSearch'] );

		if( $_REQUEST['MemberListPage'] ) {
			$pageSize = 10;

			$limitClause = ( $_REQUEST['MemberListPage'] ) . ", $pageSize";
		}

		if( !empty($_REQUEST['MemberListSearch'])  )
			$whereClauses[] = "( `Email`='$search' OR `FirstName`='$search' OR `Surname`='$search' )";

		if( is_numeric( $_REQUEST['MemberListBaseGroup'] ) ) {
			$whereClauses[] = "`GroupID`='".$_REQUEST['MemberListBaseGroup']."'";
			$join = "INNER JOIN `Group_Members` ON `MemberID`=`Member`.`ID`";
		}

		// $_REQUEST['showqueries'] = 1;

		$members = DataObject::get('Member', implode( ' AND ', $whereClauses ), $sort, $join, $limitClause );

		if( is_numeric( $_REQUEST['MemberListGroup'] ) ) {
			$baseMembers = new DataObjectSet();

			if( $members )
				foreach( $members as $member )
					if( $member->inGroup( $_REQUEST['MemberListGroup'] ) )
						$baseMembers->push( $member );
		} else
			$baseMembers = $members;

		$baseMembers = null;

		// user_error( $_REQUEST['MemberListBaseGroup'], E_USER_ERROR );

		$memberListField = new MemberTableField(
			$this,
			'MemberList',
			$_REQUEST['MemberListBaseGroup'],
			$baseMembers,
			$_REQUEST['MemberListDontShowPassword']
		);

		return $memberListField->renderWith('MemberList_Table');
	}
	
	function providePermissions() {
		return array(
			'EDIT_PERMISSIONS' => _t('SecurityAdmin.EDITPERMISSIONS', 'Edit permissions and IP addresses on each group'),
		);
	}
}

?>
