<?php

/**
 * @package cms
 * @subpackage security
 */

/**
 * Form field showing a list of members.
 * @package cms
 * @subpackage security
 */
class MemberList extends FormField {
	protected $members;
	protected $hidePassword;
	protected $pageSize;
	
	function __construct($name, $group, $members = null, $hidePassword = null, $pageLimit = 10) {
		if($group) {
			if(is_object($group)) $this->group = $group;
			else $this->group = DataObject::get_by_id('Group',$group);
		}
		
		$this->pageSize = $pageLimit;
		
		if($members) $this->members = $this->memberListWithGroupID($members, $group);
		
		$this->hidePassword = $hidePassword;
		
		parent::__construct(null);
	}
	
	function memberListWithGroupID($members, $group) {
		$newMembers = new DataObjectSet();
		foreach($members as $member) {
			$newMembers->push($member->customise(array("GroupID" => $group->ID)));
		}
		return $newMembers;
	}
	
	function FieldHolder() {
		return $this->renderWith("MemberList");
	}
	function setGroup($group) {
		$this->group = $group;
	}
	function setController($controller) {
		$this->controller = $controller;
	}
	
	function GroupID() {
		if( $this->group )
			return $this->group->ID;
		
		return '0';
	}
	
	function GetControllerName() {
		return $this->controller->class;
	}
	function Members() {
		/*if($this->members)
			$members = $this->members;
		else if($this->group) {
			$members = $this->group->Members( $this->pageSize, 0 );
			$allMembers = $this->group->Members();
			
			if( $allMembers )
				$total = $allMembers->Count();
		} else
			return null;*/
			
		if( $this->members )
			return $this->members;
			
		if( !$baseGroup )
			$baseGroup = $this->group->ID;
		
		// Debug::message( $_REQUEST['MemberListOrderByField'] );
		
		// construct the filter and sort
		
		if( $_REQUEST['MemberListOrderByField'] )
			$sort = "`" . $_REQUEST['MemberListOrderByField'] . "`" . addslashes( $_REQUEST['MemberListOrderByOrder'] );
			
		$whereClauses = array();
		
		$search = addslashes( $_REQUEST['MemberListSearch'] );
		
		if( is_numeric( $_REQUEST['MemberListStart'] ) )
			$limitClause = ( $_REQUEST['MemberListStart'] ) . ", {$this->pageSize}";
		else
			$limitClause = "0, {$this->pageSize}";	

		
		if( !empty($_REQUEST['MemberListSearch'])  )
			$whereClauses[] = "( `Email`='$search' OR `FirstName`='$search' OR `Surname`='$search' )";
			
		if( is_numeric( $_REQUEST['MemberListBaseGroup'] ) )
			$baseGroup = $_REQUEST['MemberListBaseGroup'];
		
		$whereClauses[] = "`GroupID`='".$baseGroup."'";
		$join = "INNER JOIN `Group_Members` ON `MemberID`=`Member`.`ID`";
		
		// $_REQUEST['showqueries'] = 1;
		
		$members = DataObject::get('Member', implode( ' AND ', $whereClauses ), $sort, $join, $limitClause );
		
		// $_REQUEST['showqueries'] = 0;
		
		if( is_numeric( $_REQUEST['MemberListGroup'] ) ) {
			$baseMembers = new DataObjectSet();
			
			if( $members )
				foreach( $members as $member )
					if( $member->inGroup( $_REQUEST['MemberListGroup'] ) )
						$baseMembers->push( $member );
		} else 
			$baseMembers = $members;
		
		if($members){
			$members->setPageLimits( $_REQUEST['MemberListStart'], $this->pageSize, $total );
			
			$this->members = $this->memberListWithGroupID($members, $this->group);
		}
		return $this->members;
	}
	
	function FirstLink() {
		if( !$_REQUEST['MemberListStart'] )
			return null;
		
		return "admin/security/listmembers?MemberListStart=0{$this->filterString()}";
	}
	
	function filterString() {
		
		foreach( $_REQUEST as $key => $value ) {
			if( strpos( $key, 'MemberList' ) === 0 && $key != 'MemberListStart' )
				$filterString .= "&$key=$value";
		}
	
		if( !$_REQUEST['MemberListBaseGroup'] )
			$filterString .= '&MemberListBaseGroup=' . $this->group->ID;
		
		return $filterString;
	}
	
	function MemberListStart() {
		return $_REQUEST['MemberListStart'];
	}
	
	function PrevLink() {
		if( !$_REQUEST['MemberListStart'] )
			return null;
		
		$prevStart = $_REQUEST['MemberListStart'] - $this->pageSize;
		
		if( $prevStart < 0 )
			$prevStart = 0;
		
		return "admin/security/listmembers?MemberListStart=$prevStart{$this->filterString()}";
	}
	
	function NextLink() {
		$total = $this->TotalMembers();	
		
		$lastStart = $total - ( $total % $this->pageSize );
		
		if( $_REQUEST['MemberListStart'] >= $lastStart )
			return null;
		
		return "admin/security/listmembers?MemberListStart={$this->pageSize}{$this->filterString()}";
	}
	
	function LastLink() {
		
		$total = $this->TotalMembers();	
		
		$lastStart = $total - ( $total % $this->pageSize );
		
		if( $_REQUEST['MemberListStart'] >= $lastStart )
			return null;
		
		return "admin/security/listmembers?MemberListStart=$lastStart{$this->filterString()}";
	}
	
	function PageSize() {
		return $this->pageSize;
	}
	
	function FirstMember() {
		return $_REQUEST['MemberListStart'] + 1;
	}
	
	function LastMember() {
		return $_REQUEST['MemberListStart'] + min( $_REQUEST['MemberListStart'] + $this->pageSize, $this->TotalMembers() - $_REQUEST['MemberListStart'] );
	}
	
	function TotalMembers() {
		if($this->group)  $members = $this->group->Members();
		
		if( !$members )
			return null;
			
		return $members->TotalItems();	
	}
	
	function DontShowPassword(){
		if( $this->hidePassword )
			return true;
		
		return $this->controller->class=='CMSMain'||$this->controller->class=='NewsletterAdmin';
	}
	
	function AddRecordForm() {
		if($this->DontShowPassword())
		{
			return new TabularStyle(new Form($this->controller,'AddRecordForm',
				new FieldSet(
					new TextField("FirstName", _t('MemberList.FN', 'First Name')),
					new TextField("Surname", _t('MemberList.SN', 'Surname')),
					new TextField("Email", _t('MemberList.EMAIL', 'Email')),
					new HiddenField("GroupID", null, $this->group->ID)
				),
				new FieldSet(
					new FormAction("addmember", _t('MemberList.ADD', 'Add'))
				)
			));
			
		} else {
			return new TabularStyle(new Form($this->controller,'AddRecordForm',
				new FieldSet(
					new TextField("FirstName", _t('MemberList.FN')),
					new TextField("Surname", _t('MemberList.SN')),
					new TextField("Email", _t('MemberList.EMAIL')),
					new TextField("Password", _t('MemberList.PASSWD', 'Password')),
					new HiddenField("GroupID", null, $this->group->ID)
				),
				new FieldSet(
					new FormAction("addmember", _t('MemberList.ADD'))
				)
			));
		}
	}
	
	function SearchField() {
		$field = new TextField( 'MemberListSearch', _t('MemberList.SEARCH','Search') );
		return $field->FieldHolder();
	}
	
	function OrderByField() {
		$fields = new FieldGroup( new DropdownField('MemberListOrderByField','', array(
			'FirstName' => 'FirstName',
			'Surname' => 'Surname',
			'Email' => 'Email'
		)),
		new DropdownField('MemberListOrderByOrder','',array(
			'ASC' => 'Ascending',
			'DESC' => 'Descending'
		)));
		
		$field = new FieldGroup( new LabelField( 'Order by' ), $fields );
		return $field->FieldHolder();
	}
	
	function GroupFilter() {
		
		$groups = DataObject::get('Group');
		
		$groupArray = array( '' => 'Any group' );
		
		foreach( $groups as $group )
			$groupArray[$group->ID] = $group->Title;
		
		$field = new DropdownField('MemberListGroup',_t('MemberList.FILTERBYG','Filter by group'),$groupArray );
		return $field->FieldHolder();
	}
}

?>