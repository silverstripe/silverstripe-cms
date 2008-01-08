<?php

/**
 * @package cms
 * @subpackage newsletter
 */

/**
 * Create a form that a user can use to unsubscribe from a mailing list
 */
class Unsubscribe_Controller extends Page_Controller {
    function __construct($data = null) {
    }
    function RelativeLink($action = null) {
        return "unsubscribe/$action";
    }
    
    function index() {
       	Session::clear("loggedInAs");
       	Requirements::themedCSS("form");
        // if the email address is given
        $emailAddress = addslashes( $this->urlParams['Email'] );
        $mailingListID = addslashes( $this->urlParams['MailingList'] );
        
        if(is_numeric($mailingListID)) {
        	$mailingList = DataObject::get_by_id("NewsletterType", $mailingListID);
        }
        
        // try to find the user
        if($emailAddress)
        	$member = DataObject::get_one( 'Member', "`Email`='$emailAddress'" );

        // if the email address and mailing list is given in the URL and both are valid,
        // then unsubscribe the user
        if( $member && $mailingList && $member->inGroup( $mailingList->GroupID ) ) {
            $this->unsubscribeFromList( $member, $mailingList );
            $url = "unsubscribe"."/done/".$member->Email."/".$mailingList->Title;
	      	 	Director::redirect($url);
        } elseif( $member ) {
            $listForm = $this->MailingListForm( $member );
        } else {
        	$listForm = $this->EmailAddressForm();
        }
 	
 		if($this->urlParams['Email'] == "done")
 			$listForm->sessionMessage("Thank you. You have been removed from the selected groups", "good");

        return $this->customise( array( 'Content' => $listForm->forTemplate() ) )->renderWith('Page');           
    }
    
    /**
    * Display a form with all the mailing lists that the user is subscribed to
    */
    function MailingListForm( $member = null ) {
    	$email = $this->urlParams['Email'];
       return new Unsubscribe_MailingListForm($this, 'MailingListForm', $member, $email);
    }
    
    /**
    * Display a form allowing the user to input their email address
    */
    function EmailAddressForm() {
        return new Unsubscribe_EmailAddressForm( $this, 'EmailAddressForm' );
    }
    
    /**
    * Show the lists for the user with the given email address
    */
    function showlists( $data, $form ) {
         $member = DataObject::get_one( 'Member', "`Email`='{$data['Email']}'" );
 
      
         $mailingListForm = new Unsubscribe_MailingListForm( $this, 'MailingListForm', $member, $data['Email']);
         
         return $this->customise( array( 'Content' => $mailingListForm->forTemplate() ) )->renderWith('Page');  
    }
    
    /**
    * Unsubscribe the user from the given lists.
    */
    function unsubscribe($data, $form) {
        $email = $this->urlParams['Email'];
        $member = DataObject::get_one( 'Member', "`Email`='$email'" );
        if(!$member){
        	$member = DataObject::get_one('Member', "`EmailAddress` = '$email'");
        }
        
        if( $data['MailingLists'] ) {
           foreach( array_keys( $data['MailingLists'] ) as $listID ){
            		
           		$nlType = DataObject::get_by_id( 'NewsletterType', $listID );
           		$nlTypeTitles[]= $nlType->Title;
              $this->unsubscribeFromList( $member, DataObject::get_by_id( 'NewsletterType', $listID ) );
           }
           
           $sORp = (sizeof($nlTypeTitles)>1)?"newsletters ":"newsletter ";
           //means single or plural
           $nlTypeTitles = $sORp.implode(", ", $nlTypeTitles);
	         $url = "unsubscribe/done/".$member->Email."/".$nlTypeTitles;
	      	 Director::redirect($url);
        } else {
        	$form->addErrorMessage('MailingLists', 'You need to select at least one mailing list to unsubscribe from.', 'bad');
        	Director::redirectBack();
        }
      }
    
    protected function unsubscribeFromList( $member, $list ) {
        // track unsubscriptions
        $member->Groups()->remove( $list->GroupID );
        $unsubscribeRecord = new Member_UnsubscribeRecord();
        $unsubscribeRecord->unsubscribe($member, $list);
    }
}

class Unsubscribe_MailingListForm extends Form {
    
    protected $memberEmail;
    
    function __construct( $controller, $name, $member, $email ) {
    		
        $this->memberEmail = $member->Email;
        
        $fields = new FieldSet(); 
        $actions = new FieldSet();
        
        // get all the mailing lists for this user
        $lists = $this->getMailingLists( $member );
        
        if( $lists ) {
            $fields->push( new LabelField( 'You are subscribed to the following lists:' ) );
            
            foreach( $lists as $list ) {
                $fields->push( new CheckboxField( "MailingLists[{$list->ID}]", $list->Title ) );
            }
            
            $actions->push( new FormAction('unsubscribe', 'Unsubscribe' ) );
        } else {
            $fields->push( new LabelField( "I'm sorry, but $email doesn't appear to be in any of our mailing lists." ) );   
        }
        
        parent::__construct( $controller, $name, $fields, $actions );   
    }
    
    function FormAction() {
        return $this->controller->Link() . "{$this->memberEmail}?executeForm=" . $this->name;   
    }
    
    protected function getMailingLists( $member ) {
        // get all the newsletter types that the member is subscribed to
        return DataObject::get( 'NewsletterType', "`MemberID`='{$member->ID}'", null, "LEFT JOIN `Group_Members` USING(`GroupID`)" );  
    }
}

class Unsubscribe_EmailAddressForm extends Form {

    function __construct( $controller, $name ) {
        
        $fields = new FieldSet(
            new EmailField( 'Email', 'Email address' )
        );
        
        $actions = new FieldSet(
            new FormAction( 'showlists', 'Show lists' )
        );
        
        parent::__construct( $controller, $name, $fields, $actions );    
    }
    
    function FormAction() {
        return parent::FormAction() . ( $_REQUEST['showqueries'] ? '&showqueries=1' : '' );
    }    
}

class Unsubscribe_Successful extends Form {
	function __construct($controller, $name){
		$fields = new FieldSet();
		$actions = new FieldSet();
		parent::__construct($controller, $name, $fields, $actions);
	}
	function setSuccessfulMessage($email, $newsletterTypes) {
		Requirements::themedCSS("form");
		$this->setMessage("Thank you. $email will no longer receive the $newsletterTypes.", "good");
	}
}

?>
