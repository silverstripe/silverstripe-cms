<?php

/**
 * @package cms
 * @subpackage newsletter
 */

/**
 * Displays a field for importing recipients. 
 * @package cms
 * @subpackage newsletter
 */
class RecipientImportField extends FormField {
	
	protected $memberGroup;
	protected $memberClass;
	protected $tableColumns;
	protected $table;
	protected $clientFileName;
	protected $typeID;
	
	static $column_types = array(
		'Salutation' => array( 'title', 'salutation' ),
		'FirstName' => array( 'firstname', 'christianname', 'givenname' ),
		'Surname' => array( 'lastname','surname', 'familyname' ),
		'Email' => array( 'email', 'emailaddress' ),
		'Address' => array( 'address' ),
		'PhoneNumber' => array('phone','phonenumber'),
		'JobTitle' => array( 'jobtitle' ),
		'Organisation' => array( 'organisation', 'organization' ),
		'EmailType' => array( 'htmlorplaintext', 'emailtype' )
	);
	
	static $custom_set_fields = array();
	
	public static function setCustomField( $getMapCode, $fieldName ) {
		self::$custom_set_fields[] = array( 
			'Code' => $getMapCode,
			'Field' => $fieldName
		);
	} 
	
	function __construct( $name, $title, $memberGroup, $memberClass = 'Member', $form = null ) {
		$this->memberGroup = $memberGroup;
		$this->memberClass = $memberClass;
		parent::__construct( $name, $title, null, $form );
	}
	
	function Field() {
		$frameURL = Director::absoluteBaseURL() . 'admin/newsletter/displayfilefield/' . $this->typeID;
		
		return "<iframe name=\"{$this->Name}\" frameborder=\"0\" class=\"RecipientImportField\" src=\"$frameURL\"></iframe>";
	}
	
	function setTypeID( $id ) {
		$this->typeID = $id;
	}
	
	function CustomSetFields() {
		$fields = new FieldSet();
		
		
		
		foreach( self::$custom_set_fields as $customField ) {
			eval( '$map = ' . $customField['Code'] .';' );
			
			$noneMap = array( 0 => '(Not set)' );
			
			$map = $noneMap + $map;
			
			$fields->push( new DropdownField( 'Set['.$customField['Field'].']', $customField['Field'], $map ) ); 
		}
		
		return $fields;
	}
	
	/**
	 * Returns HTML to be displayed inside the IFrame
	 */ 
	static function fileupload() {
		
	}
	
	/**
	 * Returns the table of results to be displayed in the table of
	 * details loaded from the file
	 */
	function displaytable() {
		
		// Check that a file was uploaded
		
		$tempFile = fopen( $_FILES['ImportFile']['tmp_name'], 'r' );
		
		// display some error if the file cannot be opened
		
		$this->clientFileName = $_FILES['ImportFile']['name'];
		
		while( ( $row = fgetcsv( $tempFile ) ) !== false ) {
			
			if( !$this->tableColumns ) {
				$this->parseTableHeader( $row );
			} else {
				$newRow = array();
				$newSessionTableRow = array();
				
				foreach( $row as $cell ) {
					$newRow[] = $this->parseCSVCell( $cell );
					$newSessionTableRow[] = $cell;
				}
					
				$cells = new DataObjectSet( $newRow );
				$table[] = $cells->customise( array( 'Cells' => $cells ) );
				
				$sessionTable[] = $newSessionTableRow;
			}
		}
		
		fclose( $tempFile );
		
		$this->table = new DataObjectSet( $table );
		
		Session::set("ImportFile.{$_REQUEST['ID']}", $sessionTable);
		
		return $this->renderWith( 'Newsletter_RecipientImportField_Table' );
	}
	
	/**
	 * Determines what type each column is
	 */
	function parseTableHeader( $columns ) {
		
		$columnSource = array_combine( 
			array_keys( self::$column_types ),
			array_keys( self::$column_types )
		);
		
		$columnSource = array_merge( array( 'Unknown' => 'Unknown' ), $columnSource );
		$colCount = 0;
		foreach( $columns as $cell ) {
			$columnType = $this->getColumnType( $this->parseCSVCell( $cell )->Value() );
			$this->tableColumns[] = new DropdownField( 'ImportFileColumns[' . (string)( $colCount++ ) . ']', '', $columnSource, $columnType );
		}
	}
	
	function parseCSVCell( $cell ) {
		return new RecipientImportField_Cell( $cell );
	}
	
	function getColumnType( $cell ) {
		$cell = strtolower( $cell );
		$escapedValue = preg_replace( '/[^a-z]/', '', $cell );

		foreach( self::$column_types as $type => $aliases ) {
			if( in_array( $escapedValue, $aliases ) )
				return $type;
		} 
		
		return 'Unknown';
	}
	
	function setController( $controller ) {
		$this->controller = $controller;
	}
	
	/**
	 * Set of table column headers
	 */
	function ColumnHeaders() {
		return new DataObjectSet( $this->tableColumns );
	}
	
	function Rows() {
		return $this->table;
	}
	
	function FileName() {
		return $this->clientFileName;
	}
	
	function TypeID() {
		return $this->typeID;
	}
}

/**
 * Single cell of the recipient import field
 * @package cms
 * @subpackage newsletter
 */
class RecipientImportField_Cell extends ViewableData {
	protected $value;
	
	function __construct( $value ) {
		$this->value = $value;
	}
	
	function Value() {
		return $this->value;
	}
}

/**
 * Upload form that appears in the iframe
 * @package cms
 * @subpackage newsletter
 */
class RecipientImportField_UploadForm extends Form {
	function import( $data, $form ) {
		$id = $data['ID'];
		$mailType = DataObject::get_one("NewsletterType", "ID = $id");
		if($mailType->GroupID) 
			$group = DataObject::get_one("Group", "ID = $mailType->GroupID");
			
		$recipientField = new RecipientImportField("ImportFile","Import from file", $group );
		$recipientField->setTypeID( $id );
		
		// if the file is not valid, then return an error
		if( empty( $_FILES ) || empty( $_FILES['ImportFile'] ) || $_FILES['ImportFile']['size'] == 0 )
			return $this->customise( array( 'ID' => $id, "UploadForm" => $this->controller->UploadForm( $id ), 'ErrorMessage' => 'Please choose a CSV file to import' ) )->renderWith('Newsletter_RecipientImportField'); 
		elseif( !$this->isValidCSV( $_FILES['ImportFile'] ) ) {
			/*if( file_exists( $_FILES['ImportFile']['tmp_name'] ) ) unlink( $_FILES['ImportFile']['tmp_name'] );
			unset( $_FILES['ImportFile'] );*/
			return $this->customise( array( 'ID' => $id, "UploadForm" => $this->controller->UploadForm( $id ), 'ErrorMessage' => 'The selected file was not a CSV file' ) )->renderWith('Newsletter_RecipientImportField');
		} else
			return $recipientField->displaytable();
	}
	
	function isValidCSV( $file ) {
		return preg_match( '/.*\.csv$/i', $file['name'] ) > 0;
	}
	
	function confirm( $data, $form ) {
		$id = $data['ID'];
		$mailType = DataObject::get_one("NewsletterType", "ID = $id");
		if($mailType->GroupID) 
			$group = DataObject::get_one("Group", "ID = $mailType->GroupID");
		// @TODO Look into seeing if $data['Set'] should be removed since it seems to be undefined
		return $this->importMembers( $id, $group, $data['ImportFileColumns'], $data['Set'] );
	}
	
	function cancel( $data, $form ) {
		$newForm = $this->controller->UploadForm( $data['ID'] );
		return $newForm->forTemplate();
	}
	
	function action_import( $data, $form ) {
		return $this->import( $data, $form );
	}
	
	function action_confirm( $data, $form ) {
		return $this->confirm( $data, $form );
	}
	
	function action_cancel( $data, $form ) {
		return $this->cancel( $data, $form );
	}
	
	/**
	 * Import members from the uploaded file
	 */
	protected function importMembers( $id, $group, $cols, $setFields, $primaryColType = 'Email' ) {
		
		$startTime = time();
		
		$importData = Session::get("ImportFile.{$id}");
		
		$validColumns = array_keys( RecipientImportField::$column_types );
		
		$columnMap = array_flip( $cols );
		
		// Debug::show($columnMap);
		
		// locate the primary column's index
		$primaryColumn = /*array_search( $primaryColType, $validColumns );*/ $columnMap[$primaryColType];
		
		// changed fields
		$changedFields = array();
		
		// intersect the list of valid columns with the column map to find the columns we need
		$importColumns = array_intersect( $validColumns, $cols );
		
		// statistics
		$newMembers = 0;
		$changedMembers = 0;
		$skippedMembers = 0;
		
		// the class that the imported members will become
		$newMemberClass = Object::getCustomClass( 'Member' );
		
		// for each row, add a new member or overwrite an existing member
		foreach( $importData as $newMemberRow ) {
					
			// skip rows with an empty value for the primary column
			if( empty( $newMemberRow[$primaryColumn] ) ) {
				$skippedMembers++;
				continue;
			}
			
			// remember to check if the user has unsubscribed
			$trackChanges = true;
			
			// TODO: Write DataObject::update
			$member = $this->findMember( $newMemberRow[$primaryColumn] );
			
			if( !$member ) {
				$newMembers++;
				$trackChanges = false;
				$member = Object::create("Member");
			} else {
				// skip this member if the are unsubscribed
				if( $member->Unsubscribed ) {
					$skippedMembers++;
					continue;
				}
				
				if( $member->class != $newMemberClass )
					$member->setClassName( $newMemberClass );
					
				$changedMembers++;
			}
		
			// add each of the valid columns
			foreach( $importColumns as $datum ) {
			
				// perform any required conversions
				$newValue = trim( $newMemberRow[$columnMap[$datum]] );
				$oldValue = trim( $member->$datum );
				
				// Debug::message( "$datum@{$columnMap[$datum]}" );
				
				// track the modifications to the member data
				if( $trackChanges && $newValue != $oldValue && $datum != $primaryColumn ) {
					$changedFields[] = array(
						$newMemberRow[$primaryColumn],
						"$datum:\n$oldValue",
						"$datum:\n$newValue"
					);
					
					$numChangedFields++;
				}
				
				$member->$datum = $newValue;
			}
			
			// set any set fields
			if( $setFields )
				foreach( $setFields as $fieldName => $fieldValue )
					$member->$fieldName = $fieldValue;
			
			// add member to group
			$member->write();
			$member->Groups()->add( $group->ID );
		}
		
		$numChangedFields = count( $changedFields );
		$this->notifyChanges( $changedFields );
		
		// TODO Refresh window
		$customData = array( 
			'ID' => $id, 
			"UploadForm" => $this->controller->UploadForm( $id ),
			'ImportMessage' => 'Imported new members',
			'NewMembers' => (string)$newMembers,
			'ChangedMembers' => (string)$changedMembers,
			'ChangedFields' => (string)$numChangedFields,
			'SkippedRecords' => (string)$skippedMembers,
			'Time' => time() - $startTime
		);
		return $this->customise( $customData )->renderWith('Newsletter_RecipientImportField');
	}
	
	function findMember( $email ) {
		$email = addslashes( $email );
		return DataObject::get_one( 'Member', "`Email`='$email'" );
	}
	
	function notifyChanges( $changes ) {
		$email = new Email( Email::getAdminEmail(), Email::getAdminEmail(), 'Changed Fields' );
	
		$body = "";
		
		foreach( $changes as $change ) {
			$body .= "-------------------------------\n";
			$body .= implode( ' ', $change ) . "\n";
		}
		
		$email->setBody( $body );
		$email->send();
	}
}
?>