<?php

class UserDefinedForm extends Page {
	static $add_action = "a contact form";

	static $icon = "cms/images/treeicons/task";
	
	// a list of groups that are permitted to create pages of this type.
	/*static $can_create = array(
		'Administrators'
	);*/
	
	static $need_permission = 'ADMIN';

	static $db = array(
		"EmailTo" => "Varchar",
		"EmailOnSubmit" => "Boolean",
		"SubmitButtonText" => "Varchar",
		"OnCompleteMessage" => "HTMLText"
	);
	
	static $defaults = array(
		"OnCompleteMessage" => "<p>Thanks, we've received your submission.</p>",
	);

	static $has_many = array( 
		"Fields" => "EditableFormField",
		"Submissions" => "SubmittedForm"
	);
	
	protected $fields;

	function getCMSFields($cms) {
		$fields = parent::getCMSFields($cms);

		$fields->addFieldToTab("Root.Form", new FieldEditor("Fields", "Fields", "", $this ));
		$fields->addFieldToTab("Root.Submissions", new SubmittedFormReportField( "Reports", "Received Submissions", "", $this ) );
		$fields->addFieldToTab("Root.Content.On complete", new HtmlEditorField( "OnCompleteMessage", "Show on completion",3,"",$this->OnCompleteMessage, $this ) );
		
		return $fields;
	}
	
	function FilterForm() {
		// Build fields
		$fields = new FieldSet();
		$required = array();
		
		foreach( $this->Fields() as $field ) {
			$fields->push( $field->getFilterField() );
		}
		
		// Build actions
		$actions = new FieldSet( 
			new FormAction( "filter", "Submit" )
		);
		
		// set the name of the form
		return new Form( $this, "Form", $fields, $actions );
	}
	
	/**
	 * Filter the submissions by the given criteria
	 */
	function filter( $data, $form ) {
		
		$filterClause = array( "`SubmittedForm`.`ParentID` = '{$this->ID}'" );
		
		$keywords = preg_split( '/\s+/', $data['FilterKeyword'] );
		
		$keywordClauses = array();
		
		// combine all keywords into one clause
		foreach( $keywords as $keyword ) {
		
			// escape %, \ and _ in the keyword. These have special meanings in a LIKE string
			$keyword = preg_replace( '/([%_])/', '\\\\1', addslashes( $keyword ) );
			
			$keywordClauses[] = "`Value` LIKE '%$keyword%'";	
		}
		
		if( count( $keywordClauses ) > 0 ) {
			$filterClause[] = "( " . implode( ' OR ', $keywordClauses ) . ")";
			$searchQuery = 'keywords \'' . implode( "', '", $keywords ) . '\' ';
		}
		
		$fromDate = addslashes( $data['FilterFromDate'] );
		$toDate = addslashes( $data['FilterToDate'] );
		
		// use date objects to convert date to value expected by database
		if( ereg('^([0-9]+)/([0-9]+)/([0-9]+)$', $fromDate, $parts) )
			$fromDate = $parts[3] . '-' . $parts[2] . '-' . $parts[1];
			
		if( ereg('^([0-9]+)/([0-9]+)/([0-9]+)$', $toDate, $parts) )
			$toDate = $parts[3] . '-' . $parts[2] . '-' . $parts[1];
			
		if( $fromDate ) {
			$filterClause[] = "`SubmittedForm`.`Created` >= '$fromDate'";
			$searchQuery .= 'from ' . $fromDate . ' ';
		}
			
		if( $toDate ) {
			$filterClause[] = "`SubmittedForm`.`Created` <= '$toDate'";
			$searchQuery .= 'to ' . $toDate;
		}
		
		$submittedValues = DataObject::get( 'SubmittedFormField', implode( ' AND ', $filterClause ), "", "INNER JOIN `SubmittedForm` ON `SubmittedFormField`.`ParentID`=`SubmittedForm`.`ID`" );
	
		if( !$submittedValues || $submittedValues->Count() == 0 )
			return "No matching results found";
			
		$submissions = $submittedValues->groupWithParents( 'ParentID', 'SubmittedForm' );
		
		if( !$submissions || $submissions->Count() == 0 )
			return "No matching results found";
		
		return $submissions->customise( 
			array( 'Submissions' => $submissions )
		)->renderWith( 'SubmittedFormReportField_Reports' );
	}
	
	function ReportFilterForm() {
		return new SubmittedFormReportField_FilterForm( $this, 'ReportFilterForm' );
	}
    
  function delete() {
      // remove all the fields associated with this page
      foreach( $this->Fields() as $field )
          $field->delete();
          
      parent::delete();   
  }
  
  public function customFormActions( $isReadonly = false ) {
		return new FieldSet( new TextField( "SubmitButtonText", "Text on submit button:", $this->SubmitButtonText ) );
	}

	/**
	 * Duplicate this UserDefinedForm page, and its form fields.
	 * Submissions, on the other hand, won't be duplicated.
	 */
	public function duplicate() {
		$page = parent::duplicate();
		foreach($this->Fields() as $field) {
			$newField = $field->duplicate();
			$newField->ParentID = $page->ID;
			$newField->write();
		}
		return $page;
	}
}

class UserDefinedForm_Controller extends Page_Controller {
	
	function init() {
		Requirements::javascript('jsparty/prototype-safe.js');
		Requirements::javascript('jsparty/behaviour.js');
		Requirements::javascript('mot/javascript/UserDefinedForm.js');
		
		parent::init();
	}
	
	function Form() {
		// Build fields
		$fields = new FieldSet();
		$required = array();
        
        if( !$this->SubmitButtonText )
            $this->SubmitButtonText = 'Submit';
		
		foreach( $this->Fields() as $field ) {
			$fields->push( $field->getFormField() );
			if( $field->Required )
				$required[] = $field->Name;
		}
		
		$fields->push( new HiddenField( "Referrer", "", $_SERVER['HTTP_REFERER'] ) );
		
		// Build actions
		$actions = new FieldSet( 
			new FormAction( "process", $this->SubmitButtonText )
		);
		
		// set the name of the form
		$form = new Form( $this, "Form", $fields, $actions, new RequiredFields( $required ) );
		$form->loadDataFrom($this->failover);
		return $form;
	}	
	
	function ReportFilterForm() {
		return new SubmittedFormReportField_FilterForm( $this, 'ReportFilterForm' );
	}
	
	function process( $data, $form ) {
		$submittedForm = new SubmittedForm();
		$submittedForm->SubmittedBy = Member::currentUser();
		$submittedForm->ParentID = $this->ID;
		$submittedForm->Recipient = $this->EmailTo;
		$submittedForm->write();
		
		$values = array();
		$recipientAddresses = array();
		$sendCopy = false;
		
		$submittedFields = new DataObjectSet();			
		foreach( $this->Fields() as $field ) {
			$submittedField = new SubmittedFormField();
			$submittedField->ParentID = $submittedForm->ID;
			$submittedField->Name = $field->Name;
			$submittedField->Title = $field->Title;
					
			if( $field->hasMethod( 'getValueFromData' ) )
				$submittedField->Value = $field->getValueFromData( $data );
			else
				$submittedField->Value = $data[$field->Name];
				
			$submittedField->write();
			$submittedFields->push($submittedField);
			
			if(!empty( $data[$field->Name])){
				// execute the appropriate functionality based on the form field.
				switch($field->ClassName){
					
					case "EditableEmailField" : 
					
						if($field->SendCopy){
							$recipientAddresses[] = $data[$field->Name];
							$sendCopy = true;
							$values[$field->Title] = '<a style="white-space: nowrap" href="mailto:'.$data[$field->Name].'">'.$data[$field->Name].'</a>';
						}
					
					break;
					
					case "EditableFileField" :
						
						// Returns a file type which we attach to the email. 
						$submittedfile = $field->createSubmittedField($data[$field->Name], $submittedForm);
						$file = $submittedfile->UploadedFile();
									
						$filename = $file->getFilename();
										
						// Attach the file if its less than 1MB, provide a link if its over.
						if($file->getAbsoluteSize() < 1024*1024*1){
							$attachments[] = $file;
						}
						
						// Always provide the link if present.
						if($file->ID) {
							$submittedField->Value = $values[$field->Title] = "<a href=\"". $filename ."\" title=\"". Director::absoluteBaseURL(). $filename. "\">Uploaded to: ". Director::absoluteBaseURL(). $filename . "</a>";
						} else {
							$submittedField->Value = $values[$field->Title] = "";
						}
								
					break;						
				}
				
			}elseif( $field->hasMethod( 'getValueFromData' ) ) {
				$values[$field->Title] = Convert::linkIfMatch($field->getValueFromData( $data ));
			
			} else {
				$values[$field->Title] = Convert::linkIfMatch($data[$field->Name]);
			}
			
		}	
		
		if( $this->EmailOnSubmit || $sendCopy ) {
			$emailData = array(
				"Recipient" => $this->EmailTo,
				"Sender" => Member::currentUser(),
				"Fields" => $submittedFields,
			);
			
			$email = new UserDefinedForm_SubmittedFormEmail($submittedFields);			
			$email->populateTemplate($emailData);
			$email->setTo( $this->EmailTo );
			$email->setSubject( $this->Title );

			// add attachments to email (<1MB)
			if($attachments){
				foreach($attachments as $file){
					$email->attachFile($filename,$filename);
				}
			}
			
			$email->send();
					
			// send to each of email fields
			foreach( $recipientAddresses as $addr ) {
				$email->setTo( $addr );
				$email->send();
			}
		}
		
		$custom = $this->customise(array(
			"Content" => $this->customise( array( 'Link' => $data['Referrer'] ) )->renderWith('ReceivedFormSubmission'),
			"Form" => " ",
		));
		
		return $custom->renderWith('Page');
	}
}

class UserDefinedForm_SubmittedFormEmail extends Email_Template {
	protected $ss_template = "SubmittedFormEmail";
	protected $from = '$Sender.Email';
	protected $to = '$Recipient.Email';
	protected $subject = "Submission of form";
	protected $data;
	
	function __construct($values) {
		parent::__construct();
		
		$this->data = $values;
	}
	
	function Data() {
		return $this->data;
	}
}

?>
