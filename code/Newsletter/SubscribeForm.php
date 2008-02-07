<?php

/**
 * @package cms
 * @subpackage newsletter
 */

/**
 * Page type for creating a page that contains a form that visitors can use to subscript to a newsletter.
 * @package cms
 * @subpackage newsletter
 */
class SubscribeForm extends UserDefinedForm {
	static $add_action = "a newsletter subscription form";

    static $required_fields = array(
      'Email address' => 'EditableEmailField(CustomParameter=Email,CanDelete=0)',
      'First name' => 'EditableTextField(CustomParameter=FirstName)',
      'Last name' => 'EditableTextField(CustomParameter=Surname)',
      'Address' => 'EditableTextField(Rows=3,CustomParameter=Address)',
      'Job title' => 'EditableTextField(CustomParameter=JobTitle)',
      'Organisation' => 'EditableTextField(CustomParameter=Organisation)',
      'Mail format' => 'EditableRadioField(Options=1:HTML,Options=0:Text__only,CustomParameter=HTMLEmail)'
    );
    
    static $db = array(
      'Subscribe' => 'Boolean',
      'AllNewsletters' => 'Boolean',
      'Subject' => 'Varchar'
    );

	static $defaults = array(
		"OnCompleteMessage" => "<p>Thanks, you have been added to our mailing list.</p>",
	);
    
    static $has_many = array(
      'Newsletters' => 'NewsletterType'
    );
    
    function __construct( $data = null, $isSingleton = false ) {
        parent::__construct( $data, $isSingleton );
        
        if( $data || $isSingleton )
            return;
                
        $this->addDefaultFields(); 
    } 
    
    private function addDefaultFields() {
        $f = $this->Fields();       

        // check that the required fields exist
        $count = 1;
        
        foreach( self::$required_fields as $defaultName => $typeString ) {
            
            list( $type, $typeValue ) = $this->parseType( $typeString );
            $newField = new $type();
            $newField->Name = "Field" . (string)$count;
            $newField->Title = $defaultName;
            
            // TODO Map the field to a particular action
            
            if( !empty( $typeValue ) )  {
                $newField->prepopulate( $typeValue );  
            }

            $newField->ParentID = $this->ID;
            $newField->Sort = $count;
			$newField->write();
            $count++;
        }
    }

	public function write($showDebug = false, $forceInsert = false, $forceWrite = false) {
		$isNew = (!$this->ID);
		parent::write($showDebug, $forceInsert, $forceWrite);
		if($isNew) $this->addDefaultFields();		
	}
    
    public function Newsletters() {
    	$components = $this->getComponents('Newsletters');
    	return $components;
    }
    
    public function customFormActions( $isReadonly = false ) {
        
        $fields = parent::customFormActions( $isReadonly );
        
        // get the newsletters in the system
        $newsletterTypes = DataObject::get( 'NewsletterType' );       
        
        $availableNewsletters = array();
        $nlCheckboxes = array();

        
        foreach( $this->Newsletters() as $subscribeTo )
            $availableNewsletters[] = $subscribeTo->ID;
        

        
        // create a checkbox for each newsletter
        if($newsletterTypes && is_object($newsletterTypes)) {
           foreach( $newsletterTypes as $newsletterType )
              $nlCheckboxes[] = new CheckboxField( "CustomNewsletters[{$newsletterType->ID}]", $newsletterType->Title, in_array( $newsletterType->ID, $availableNewsletters ) );
        }
        $fields->push( new OptionsetField( 'AllNewsletters', '', array( 
          	1 => 'All newsletters',
          	0 => 'Specific newsletters'
          	),
		$this->AllNewsletters
	));
          
        $fields->push( new CompositeField( $nlCheckboxes ));
        $fields->push( new TextField( 'Subject', 'Subject line on confirmation', $this->Subject ) );

        return $fields;
    }
    
    function parseType( $typeString ) {
        if( preg_match('/^([A-Za-z_]+)\(([^)]+)\)$/', $typeString, $match ) )
            return array( $match[1], $match[2] );
        
        return array( $typeString, null );
    }
    
    /**
    * 
    */
    public function processNewFormFields() {
    
    }
    
    /**
    * Saves data related to any custom actions this form performs when submitted
    */
    public function customFormSave( $data, $form ) {
        
        $newsletters = $this->Newsletters();
        
        $this->Subscribe = !empty( $data['Subscribe'] );
        $this->AllNewsletters = !empty( $data['AllNewsletters'] );
        $this->Subject = $data['Subject'];
        // Note: $data['CustomNewsletters'] was changed to $_REQUEST['CustomNewsletters'] in order to fix
        // to fix "'Specific newsletters' option in 'newsletter subscription form' page type does not work" bug
        // See: http://www.silverstripe.com/bugs/flat/1675
        if( !empty( $_REQUEST['CustomNewsletters'] ) ) {
            /*foreach( $newsletters as $newsletter ) {
              if( $data['CustomNewsletters'][$newsletter->ID] == 'on' ) {
                $newsletters->add( $newsletter->ID );
              } else {
              	$newsletters->remove( $newsletter->ID );
              }
            }*/
						
						if( isset($_REQUEST['CustomNewsletters'][0]) )
							unset( $_REQUEST['CustomNewsletters'][0] );
						
            $newsletters->setByIDList( array_keys( $_REQUEST['CustomNewsletters'] ) );                  
        } else {
        	$this->AllNewsletters = true;
        	$newsletters->removeAll();
        }
    }
}

/**
 * Email for sending subscribe form submissions.
 * @package cms
 * @subpackage newsletter
 */
class SubscribeForm_SubscribeEmail extends Email_Template {
    protected $to = '$Email';
    protected $subject = '$Subject';
    protected $ss_template = 'SubscribeEmail';
    protected $from = '';   
}

/**
 * Controller for the SubscribeForm page
 * @package cms
 * @subpackage newsletter
 */
class SubscribeForm_Controller extends UserDefinedForm_Controller {
 
    function process( $data, $form ) {
        // Add the user to the mailing list
        $member = Object::create("Member");
        
        // $_REQUEST['showqueries'] = 1;
        
        // map the editables to the data
        
        foreach( $this->Fields() as $editable ) {
            $field = $editable->CustomParameter;
            if( !$field )
                continue;
            
            // Debug::message( $editable->Name . '->' . $field );
            
            // if( $member->hasField( $field ) )
                $member->$field = $data[$editable->Name];   
        }

        // need to write the member record before adding the user to groups
        $member->write(); 
        
        $newsletters = array();
        
        // Add member to the selected newsletters
        if( isset($data['Newsletters'])) foreach( $data['Newsletters'] as $listID ) {
            
            if( !is_numeric( $listID ) )
                continue;
            
            // get the newsletter
            $newsletter = DataObject::get_by_id( 'NewsletterType', $listID );
            
            if( !$newsletter )
                continue;
            
            $newsletters[] = $newsletter;    
            // Debug::show( $newsletter->GroupID );
                
            $member->Groups()->add( $newsletter->GroupID );    
        }
        
        // email the user with their details
        $templateData = array(
            'Email' => $member->Email,
            'FirstName' => $member->FirstName,
            'Newsletters' => new DataObjectSet( $newsletters ),
            'UnsubscribeLink' => Director::baseURL() . 'unsubscribe/' . $member->Email
        );
        
        $email = new SubscribeForm_SubscribeEmail(); 
        $email->setFrom( Email::getAdminEmail() );
        $email->setSubject( $this->Subject );
        
        $email->populateTemplate( $templateData );
        $email->send();
        
        $parentHTML = parent::process( $data, $form ); 
        
        $templateData['Link'] = $data['Referrer'];
        $templateData['UnsubscribeLink'] = Director::baseURL() . 'unsubscribe';
        
		    $custom = $this->customise(array(
					"Content" => $this->customise( $templateData )->renderWith('SubscribeSubmission')
				));
		
				return $custom->renderWith('Page');  
    }
    
    function Form() {
        $form = parent::Form();
        
        if( $this->AllNewsletters )
            $newsletterList = DataObject::get('NewsletterType');
        else
            $newsletterList = $this->Newsletters();
        
        $newsletters = array();
        
        // get the newsletter types to display on the form
        foreach( $newsletterList as $newsletter )
            $newsletters[$newsletter->ID] = $newsletter->Title;
        
        $form->Fields()->push( new CheckboxSetField( 'Newsletters', 'Subscribe to lists', $newsletters ) );
        
        $validator = $form->getValidator();
       	$validator->addRequiredField( 'Newsletters' );
       	$form->setValidator( $validator );
       
        return $form;
    }   
}
?>