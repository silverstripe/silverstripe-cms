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
 */
class MemberTableField extends ComplexTableField {
	protected $members;
	protected $hidePassword;
	protected $pageSize;
	protected $detailFormValidator;
	protected $group;

	protected $template = "MemberTableField";

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
			"FirstName" => "Firstname",
			"Surname" => "Surname",
			"Email" => "Email"
		);

		$csvFieldList = $fieldList;
		foreach( self::$addedCsvFields as $key => $value ) {
			$csvFieldList[$key] = $value;
		}

		foreach( self::$addedFields as $key => $value ) {
			$fieldList[$key] = $value;
		}

		if(!$hidePassword) {
			$fieldList["Password"] = "Password";
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

		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields);

		Requirements::javascript("cms/javascript/MemberTableField.js");

		// construct the filter and sort
		if(isset($_REQUEST['MemberOrderByField'])) {
			$this->sourceSort = "`" . Convert::raw2sql($_REQUEST['MemberOrderByField']) . "`" . Convert::raw2sql( $_REQUEST['MemberOrderByOrder'] );
		}

		// search
		$search = isset($_REQUEST['MemberSearch']) ? Convert::raw2sql($_REQUEST['MemberSearch']) : null;
		if(!empty($_REQUEST['MemberSearch'])) {
			//$this->sourceFilter[] = "( `Email` LIKE '%$search%' OR `FirstName` LIKE '%$search%' OR `Surname` LIKE '%$search%' )";
			$sourceF = "( ";
			foreach( $fieldList as $k => $v )
				$sourceF .= "`$k` LIKE '%$search%' OR ";
			$this->sourceFilter[] = substr( $sourceF, 0, -3 ) . ")";
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

	function DetailForm() {
		$ID = Convert::raw2xml(isset($_REQUEST['ctf']['ID'])
													   ? $_REQUEST['ctf']['ID']
														 : '');
		$childID = isset($_REQUEST['ctf']['childID']) ? Convert::raw2xml($_REQUEST['ctf']['childID']) : 0;
		$childClass = Convert::raw2xml($_REQUEST['fieldName']);
		$methodName = isset($_REQUEST['methodName']) ? $_REQUEST['methodName'] : '';

		if($methodName == "add") {
			$parentIdName = $this->getParentIdName($childClass,$this->getParentClass());
			if(!$parentIdName) {
				user_error("ComplexTableField::DetailForm() Dataobject does not seem to have an 'has-one'-relationship", E_USER_WARNING);
				return;
			}
			$this->detailFormFields->push(new HiddenField('parentClass'," ",$this->getParentClass()));
		}

		// the ID field confuses the Controller-logic in finding the right view for ReferencedField
		$this->detailFormFields->removeByName('ID');

		$this->detailFormFields->push(new HiddenField("ctf[ID]"," ",$ID));
		// add a namespaced ID instead thats "converted" by saveComplexTableField()
		$this->detailFormFields->push(new HiddenField("ctf[childID]","",$childID));
		$this->detailFormFields->push(new HiddenField("ClassName","",$this->sourceClass));
		
		$form = new MemberTableField_Popup($this, "DetailForm", $this->detailFormFields, $this->sourceClass, $methodName == "show", $this->detailFormValidator);

		if (is_numeric($childID)) {
			if ($methodName == "show" || $methodName == "edit") {
				$childData = DataObject::get_by_id($this->sourceClass, $childID);
				$form->loadDataFrom($childData);
			}
		}

		if ($methodName == "show") {
			$form->makeReadonly();
		}

		return $form;
	}

	function SearchForm() {
		$searchFields = new FieldGroup(
			new TextField('MemberSearch', 'Search'),
			new HiddenField("ctf[ID]",'',$this->group->ID),
			new HiddenField('MemberFieldName','',$this->name),
			new HiddenField('MemberDontShowPassword','',$this->hidePassword)
		);

		$orderByFields = new FieldGroup(
			new LabelField('Order by'),
			new FieldSet(
				new DropdownField('MemberOrderByField','', array(
				'FirstName' => 'FirstName',
				'Surname' => 'Surname',
				'Email' => 'Email'
				)),
				new DropdownField('MemberOrderByOrder','',array(
					'ASC' => 'Ascending',
					'DESC' => 'Descending'
				))
			)
		);

		$groups = DataObject::get('Group');
		$groupArray = array('' => 'Any group');
		foreach( $groups as $group ) {
			$groupArray[$group->ID] = $group->Title;
		}
		$groupFields = new DropdownField('MemberGroup','Filter by group',$groupArray );

		$actionFields = new LiteralField('MemberFilterButton','<input type="submit" name="MemberFilterButton" value="Filter" id="MemberFilterButton"/>');

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
			FormResponse::status_messsage('Adding failed', 'bad');
		}

		$className = $this->stat('data_class');
		$record = new $className();

		$record->update($data);
		$record->write();
		
		// To Avoid duplication in the Group_Members table if the ComponentSet.php is not modified just uncomment le line below
		
		//if( ! $record->isInGroup( $data['ctf']['ID'] ) )
			$record->Groups()->add( $data['ctf']['ID'] );

		$this->sourceItems();

		// TODO add javascript to highlight added row (problem: might not show up due to sorting/filtering)
		FormResponse::update_dom_id($this->id(), $this->renderWith($this->template), true);
		FormResponse::status_message(_t('MemberTableField.ADDEDTOGROUP','Added member to group'), 'good');

		return FormResponse::respond();
	}

	/**
	 * Custom delete implementation:
	 * Remove member from group rather than from the database
	 */
	function delete() {
		$groupID = Convert::raw2sql($_REQUEST["ctf"]["ID"]);
		$memberID = Convert::raw2sql($_REQUEST["ctf"]["childID"]);
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
		return "Group";
	}

	function getParentIdName($childClass,$parentClass){
		return "GroupID";
	}


	/**
	 * #################################
	 *           Custom Functions
	 * #################################
	 */
	function memberListWithGroupID($members, $group) {
		$newMembers = new DataObjectSet();
		foreach($members as $member) {
			$newMembers->push($member->customise(array("GroupID" => $group->ID)));
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
		$fields->push(new HiddenField("ctf[ID]", null, $this->group->ID));

		return new TabularStyle(new Form($this->controller,'AddRecordForm',
			$fields,
			new FieldSet(
				new FormAction("addtogroup", _t('MemberTableField.ADD','Add'))
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
		$limitClause = "";
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
		$this->unpagedSourceItems = $this->group->Members( "", "", $this->sourceFilter, $this->sourceSort );
		$this->totalCount = ($this->sourceItems) ? $this->sourceItems->TotalItems() : 0;
		return $this->sourceItems;
	}

	function TotalCount() {
		$this->sourceItems(); // Called for its side-effect of setting total count
		return $this->totalCount;
	}
}





class MemberTableField_Popup extends ComplexTableField_Popup {
	function __construct($controller, $name, $fields, $sourceClass, $readonly=false, $validator = null) {

		// DO NOT CHANGE THE ORDER OF THESE JS FILES. THESE ARE ONLY REQUIRED FOR THIS INSTANCE !!!11onetwo

		parent::__construct($controller, $name, $fields, $sourceClass, $readonly, $validator);

		Requirements::javascript("cms/javascript/MemberTableField.js");
		Requirements::javascript("cms/javascript/MemberTableField_popup.js");
	}


	function saveComplexTableField() {
		$id = Convert::raw2sql($_REQUEST['ctf']['childID']);

		if (is_numeric($id)) {
			$childObject = DataObject::get_by_id($this->sourceClass, $id);
		} else {
			$childObject = new $this->sourceClass();
		}
		$this->saveInto($childObject);
		$childObject->write();

		$childObject->Groups()->add($_REQUEST['ctf']['ID']);

		// if ajax-call in an iframe, close window by javascript, else redirect to referrer
		if(!Director::is_ajax()) {
			Director::redirect(substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],"?")));
		}
	}

}
?>