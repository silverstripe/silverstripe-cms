<?php

class Newsletter extends DataObject {
	
	/**
	 * Returns a FieldSet with which to create the CMS editing form.
	 * You can use the extend() method of FieldSet to create customised forms for your other
	 * data objects.
	 */
	function getCMSFields($controller = null) {
		require_once("forms/Form.php");

		$group = DataObject::get_by_id("Group", $this->Parent()->GroupID);
		
		$ret = new FieldSet(
			new TabSet("Root",
				$mailTab = new Tab("Mail",
					new TextField("Subject", "Subject", $this->Subject),
					new HtmlEditorField("Content", "Content")
				)
			)
		);
		
		if( $this->Status != 'Draft' ) {
			$mailTab->push( new ReadonlyField("SendDate", "Sent at", $this->SendDate) );
		} 
		
		
		return $ret;
	}
	
	function getTitle() {
		return $this->getField('Subject');
	}

	function getNewsletterType() {
		return DataObject::get_by_id('NewsletterType', $this->ParentID);
	}

	static $db = array(
		"Status" => "Enum('Draft, Send', 'Draft')",
		"Content" => "HTMLText",
		"Subject" => "Varchar(255)",
		"SentDate" => "Datetime",

	);
	
	static $has_one = array(
		"Parent" => "NewsletterType",
	);
	
	static $has_many = array(
		"Recipients" => "Newsletter_Recipient",
	);

	static function newDraft( $parentID, $subject, $content ) {
    if( is_numeric( $parentID ) ) {
        $newsletter = new Newsletter();
        $newsletter->Status = 'Draft';
        $newsletter->Title = $newsletter->Subject = $subject;
        $newsletter->ParentID = $parentID;
        $newsletter->Content = $content;
        $newsletter->write();
    } else {
        user_error( $parentID, E_USER_ERROR );   
    }
        
    return $newsletter;     
  }
}

class Newsletter_Recipient extends DataObject {
	static $db = array(
		"ParentID" => "Int",
	);
	static $has_one = array(
		"Member" => "Member",
	);
}

class Newsletter_Email extends Email_Template {
	protected $nlType;
	
	function __construct($nlType) {
		$this->nlType = $nlType;
		parent::__construct();
	}
	
	function setTemplate( $template ) {
		$this->ss_template = $template;
	}
	
	function UnsubscribeLink(){
		$emailAddr = $this->To();
		$nlTypeID = $this->nlType->ID;
		return Director::absoluteBaseURL()."unsubscribe/$emailAddr/$nlTypeID";
	}
}
?>