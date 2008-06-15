<?php
/**
 * Enhances {ComplexTableField} with the ability to list groups and given members.
 * It is based around groups, so it deletes Members from a Group rather than from the entire system.
 *
 * In contrast to the original implementation, the URL-parameters "ParentClass" and "ParentID" are used
 * to specify "Group" (hardcoded) and the GroupID-relation.
 *
 * Returns either:
 * - provided members
 * - members of a provided group
 * - all members
 * - members based on a search-query
 * @package cms
 * @subpackage security
 */
class MemberTableField extends ComplexTableField {
	
	protected $members;
	
	protected $hidePassword;
	
	protected $pageSize;
	
	protected $detailFormValidator;
	
	protected $group;

	protected $template = "MemberTableField";

	public $popupClass = 'MemberTableField_Popup';
	
	static $data_class = "Member";

	protected $permissions = array(
		"add",
		"edit",
		"delete"
	);
	
	private static $addedPermissions = array();
	
	private static $addedFields = array();
	
	private static $addedCsvFields = array();

	public static function addPermissions( $addingPermissionList ) {
		self::$addedPermissions = $addingPermissionList;
	}

	public static function addMembershipFields( $addingFieldList, $addingCsvFieldList = null ) {
		self::$addedFields = $addingFieldList;
		$addingCsvFieldList == null ? self::$addedCsvFields = $addingFieldList : self::$addedCsvFields = $addingCsvFieldList;
  	}

	function __construct($controller, $name, $group, $members = null, $hidePassword = true, $pageLimit = 10) {

		if($group) {
			if(is_object($group)) {
				$this->group = $group;
			} else if(is_numeric($group)){
				$this->group = DataObject::get_by_id('Group',$group);
			}
		} else if(is_numeric($_REQUEST['ctf'][$this->Name()]["ID"])) {
			$this->group = DataObject::get_by_id('Group',$_REQUEST['ctf'][$this->Name()]["ID"]);
		}


		$sourceClass = $this->stat("data_class");

		foreach( self::$addedPermissions as $permission )
			array_push( $this->permissions, $permission );

		$fieldList = array(
			"FirstName" => _t('MemberTableField.FIRSTNAME', 'Firstname'),
			"Surname" => _t('MemberTableField.SURNAME', 'Surname'),
			"Email" => _t('MemberTableField.EMAIL', 'Email')
		);

		$csvFieldList = $fieldList;
		foreach( self::$addedCsvFields as $key => $value ) {
			$csvFieldList[$key] = $value;
		}

		foreach( self::$addedFields as $key => $value ) {
			$fieldList[$key] = $value;
		}

		if(!$hidePassword) {
			$fieldList["SetPassword"] = "Password"; 
		}
		
		if(isset($_REQUEST['ctf']['childID']) && $memberID = $_REQUEST['ctf']['childID']) {
			$SNG_member = DataObject::get_by_id($this->stat("data_class"),$_REQUEST['ctf']['childID']); 
		} else {
			$SNG_member = singleton(Object::getCustomClass($this->stat("data_class")));
		}
		$detailFormFields = $SNG_member->getCMSFields();
		$this->detailFormValidator =  $SNG_member->getValidator();

		$this->pageSize = $pageLimit;

		// Legacy: Use setCustomSourceItems() instead.
		if($members) {
			$this->customSourceItems = $this->memberListWithGroupID($members, $group);
		}

		$this->hidePassword = $hidePassword;

		parent::__construct($controller, $name, $sourceClass, $fieldList);

		Requirements::javascript('cms/javascript/MemberTableField.js');
		Requirements::javascript("cms/javascript/MemberTableField_popup.js");

		// construct the filter and sort
		if(isset($_REQUEST['MemberOrderByField'])) {
			$this->sourceSort = '`' . Convert::raw2sql($_REQUEST['MemberOrderByField']) . '`' . Convert::raw2sql( $_REQUEST['MemberOrderByOrder'] );
		}

		// search
		$SQL_search = isset($_REQUEST['MemberSearch']) ? Convert::raw2sql($_REQUEST['MemberSearch']) : null;
		if(!empty($_REQUEST['MemberSearch'])) {
			$searchFilters = array();
			foreach($SNG_member->stat('searchable_fields') as $fieldName => $fieldSpec) {
				$searchFilters[] = "`$fieldName` LIKE '%{$SQL_search}%'";
			}
			$this->sourceFilter[] = '(' . implode(' OR ', $searchFilters) . ')';
		}

		// filter by groups
		// TODO Not implemented yet
		if(isset($_REQUEST['ctf'][$this->Name()]['GroupID']) && is_numeric($_REQUEST['ctf'][$this->Name()]['GroupID'])) {
			$this->sourceFilter[] = "`GroupID`='{$_REQUEST['ctf'][$this->Name()]['GroupID']}'";
		} elseif($this->group) {
			//$this->sourceFilter[] = "`GroupID`='{$this->group->ID}'";
			// If the table is not clean (without duplication), the  total and navigation wil not work well, so uncheck the big line below
			$this->sourceFilter[] = "`Group_Members`.`ID` IN (SELECT `ID` FROM `Group_Members` WHERE `GroupID`='{$this->group->ID}' GROUP BY `MemberID` HAVING MIN(`ID`))";
		}

		$this->sourceJoin = " INNER JOIN `Group_Members` ON `MemberID`=`Member`.`ID`";
		$this->setFieldListCsv( $csvFieldList );
	}

	/**
	 * Overridden functions
	 */


	function sourceID() {
		return $this->group->ID;
	}

	function AddLink() {
		return "{$this->PopupBaseLink()}&methodName=add";
	}

	function SearchForm() {
		$searchFields = new FieldGroup(
			new TextField('MemberSearch', _t('MemberTableField.SEARCH', 'Search')),
			new HiddenField("ctf[ID]",'',$this->group->ID),
			new HiddenField('MemberFieldName','',$this->name),
			new HiddenField('MemberDontShowPassword','',$this->hidePassword)
		);

		$orderByFields = new FieldGroup(
			new LabelField(_t('MemberTableField.ORDERBY', 'Order by')),
			new FieldSet(
				new DropdownField('MemberOrderByField','', array(
				'FirstName' => _t('MemberTableField.FIRSTNAME', 'FirstName'),
				'Surname' => _t('MemberTableField.SURNAME', 'Surname'),
				'Email' => _t('MemberTableField.EMAIL', 'Email')
				)),
				new DropdownField('MemberOrderByOrder','',array(
					'ASC' => _t('MemberTableField.ASC', 'Ascending'),
					'DESC' => _t('MemberTableField.DESC', 'Descending')
				))
			)
		);

		$groups = DataObject::get('Group');
		$groupArray = array('' => _t('MemberTableField.ANYGROUP', 'Any group'));
		foreach( $groups as $group ) {
			$groupArray[$group->ID] = $group->Title;
		}
		$groupFields = new DropdownField('MemberGroup', _t('MemberTableField.FILTERBYGROUP', 'Filter by group'),$groupArray );

		$actionFields = new LiteralField('MemberFilterButton','<input type="submit" class="action" name="MemberFilterButton" value="'._t('MemberTableField.FILTER', 'Filter').'" id="MemberFilterButton"/>');

		$fieldContainer = new FieldGroup(
				$searchFields,
	//			$orderByFields,
	//			$groupFields,
				$actionFields
		);

		return $fieldContainer->FieldHolder();
	}


	/**
	 * Add existing member to group rather than creating a new member
	 */
	function addtogroup() {
		$data = $_REQUEST;
		unset($data['ID']);

		if(!is_numeric($data['ctf']['ID'])) {
		  FormResponse::status_messsage(_t('MemberTableField.ADDINGFIELD', 'Adding failed'), 'bad');
		}

		$className = $this->stat('data_class');
		$record = new $className();

		$record->update($data);
		
		$valid = $record->validate();
		if($valid->valid()) {
			$record->write();

			// To Avoid duplication in the Group_Members table if the ComponentSet.php is not modified just uncomment le line below

			//if( ! $record->isInGroup( $data['ctf']['ID'] ) )
				$record->Groups()->add( $data['ctf']['ID'] );

			$this->sourceItems();

			// TODO add javascript to highlight added row (problem: might not show up due to sorting/filtering)
			FormResponse::update_dom_id($this->id(), $this->renderWith($this->template), true);
			FormResponse::status_message(_t('MemberTableField.ADDEDTOGROUP','Added member to group'), 'good');
		
		} else {
			FormResponse::status_message(Convert::raw2xml("I couldn't add that user to this group:\n\n" . $valid->starredlist()), 'bad');
		}

		return FormResponse::respond();
	}

	/**
	 * Custom delete implementation:
	 * Remove member from group rather than from the database
	 */
	function delete() {
		$groupID = Convert::raw2sql($_REQUEST['ctf']['ID']);
		$memberID = Convert::raw2sql($_REQUEST['ctf']['childID']);
		if(is_numeric($groupID) && is_numeric($memberID)) {
			$member = DataObject::get_by_id('Member', $memberID);
			$member->Groups()->remove($groupID);
		} else {
			user_error("MemberTableField::delete: Bad parameters: Group=$groupID, Member=$memberID", E_USER_ERROR);
		}

		return FormResponse::respond();

	}



	/**
	 * #################################
	 *           Utility Functions
	 * #################################
	 */
	function getParentClass() {
		return 'Group';
	}

	function getParentIdName($childClass,$parentClass){
		return 'GroupID';
	}


	/**
	 * #################################
	 *           Custom Functions
	 * #################################
	 */
	function memberListWithGroupID($members, $group) {
		$newMembers = new DataObjectSet();
		foreach($members as $member) {
			$newMembers->push($member->customise(array('GroupID' => $group->ID)));
		}
		return $newMembers;
	}

	function setGroup($group) {
		$this->group = $group;
	}
	function setController($controller) {
		$this->controller = $controller;
	}

	function GetControllerName() {
		return $this->controller->class;
	}

	/**
	 * Add existing member to group by name (with JS-autocompletion)
	 */
	function AddRecordForm() {
		$fields = new FieldSet();
		foreach($this->FieldList() as $fieldName=>$fieldTitle) {
			$fields->push(new TextField($fieldName));
		}
		$fields->push(new HiddenField('ctf[ID]', null, $this->group->ID));

		return new TabularStyle(new Form($this->controller,'AddRecordForm',
			$fields,
			new FieldSet(
				new FormAction('addtogroup', _t('MemberTableField.ADD','Add'))
			)
		));
	}

	/**
	 * Cached version for getting the appropraite members for this particular group.
	 *
	 * This includes getting inherited groups, such as groups under groups.
	 */
	function sourceItems(){
		// Caching.
		if($this->sourceItems) {
			return $this->sourceItems;
		}

		// Setup limits
		$limitClause = '';
		if(isset($_REQUEST['ctf'][$this->Name()]['start']) && is_numeric($_REQUEST['ctf'][$this->Name()]['start'])) {
			$limitClause = ($_REQUEST['ctf'][$this->Name()]['start']) . ", {$this->pageSize}";
		} else {
			$limitClause = "0, {$this->pageSize}";

		}
				
		// We use the group to get the members, as they already have the bulk of the look up functions
		$start = isset($_REQUEST['ctf'][$this->Name()]['start']) ? $_REQUEST['ctf'][$this->Name()]['start'] : 0; 
		$this->sourceItems = $this->group->Members( 
 	        $this->pageSize, // limit 
 	        $start, // offset 
	        $this->sourceFilter,
	        $this->sourceSort
        );
		$this->unpagedSourceItems = $this->group->Members( '', '', $this->sourceFilter, $this->sourceSort );
		$this->totalCount = ($this->sourceItems) ? $this->sourceItems->TotalItems() : 0;
		return $this->sourceItems;
	}

	function TotalCount() {
		$this->sourceItems(); // Called for its side-effect of setting total count
		return $this->totalCount;
	}
}

/**
 * Popup window for {@link MemberTableField}.
 * @package cms
 * @subpackage security
 */
class MemberTableField_Popup extends ComplexTableField_Popup {
	
	function __construct($controller, $name, $fields, $sourceClass, $readonly=false, $validator = null) {
		parent::__construct($controller, $name, $fields, $sourceClass, $readonly, $validator);

		Requirements::javascript('cms/javascript/MemberTableField.js');
		Requirements::javascript('cms/javascript/MemberTableField_popup.js');
	}

	/**
	 * Same behaviour as parent class, but adds the member to the passed GroupID.
	 *
	 * @return string
	 */
	function saveComplexTableField() {
		if(isset($_REQUEST['ctf']['childID']) && is_numeric($_REQUEST['ctf']['childID'])) {
			$childObject = DataObject::get_by_id($this->sourceClass, $_REQUEST['ctf']['childID']);
		} else {
			$childObject = new $this->sourceClass();
			$this->fields->removeByName('ID');
		}

		$this->saveInto($childObject);

		$form = $this->controller->DetailForm($childObject->ID);
	
		$valid = $childObject->validate();
		if($valid->valid()) {
			$childObject->write();

			// custom behaviour for MemberTableField: add member to current group
			$childObject->Groups()->add($_REQUEST['GroupID']);

			$form->sessionMessage("Changes saved.",  'good');
		} else {
			$form->sessionMessage(Convert::raw2xml("I couldn't save your changes:\n\n" . $valid->starredList()), 'bad');
		}
		
	
		// if ajax-call in an iframe, update window
		if(Director::is_ajax()) {
			// Newly saved objects need their ID reflected in the reloaded form to avoid double saving 
			$form->loadDataFrom($childObject);
			
			if($valid->valid()) {
				FormResponse::update_dom_id($form->FormName(), $form->formHtmlContent(), true, 'update');
			} else {
				FormResponse::load_form($form->forTemplate(), $form->FormName());
			}

			return FormResponse::respond();
		} else {
			Director::redirectBack();
		}
	}
}
?>
