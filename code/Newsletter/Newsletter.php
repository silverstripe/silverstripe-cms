<?php

/**
 * @package cms
 * @subpackage newsletter
 */

/**
 * Single newsletter instance.  Each Newsletter belongs to a NewsletterType.
 * @package cms
 * @subpackage newsletter
 */
class Newsletter extends DataObject {
	
	/**
	 * Returns a FieldSet with which to create the CMS editing form.
	 * You can use the extend() method of FieldSet to create customised forms for your other
	 * data objects.
	 */
	function getCMSFields($controller = null) {
		require_once("forms/Form.php");

		$group = DataObject::get_by_id("Group", $this->Parent()->GroupID);
		$sent_status_report = $this->renderWith("Newsletter_SentStatusReport");
		$ret = new FieldSet(
			new TabSet("Root",
				$mailTab = new Tab(_t('Newsletter.NEWSLETTER', 'Newsletter'),
					new TextField("Subject", _t('Newsletter.SUBJECT', 'Subject'), $this->Subject),
					new HtmlEditorField("Content", _t('Newsletter.CONTENT', 'Content'))
				),
				$sentToTab = new Tab(_t('Newsletter.SENTREPORT', 'Sent Status Report'),
					new LiteralField("Sent Status Report", $sent_status_report)
				)
			)
		);
		
		if( $this->Status != 'Draft' ) {
		        $mailTab->push( new ReadonlyField("SendDate", _t('Newsletter.SENTAT', 'Sent at'), $this->SendDate) );
		} 
		
		
		return $ret;
	}

	/**
	 * Returns a DataObject listing the recipients for the given status for this newsletter
	 *
	 * @param string $result 3 possible values: "Sent", (mail() returned TRUE), "Failed" (mail() returned FALSE), or "Bounced" ({@see $email_bouncehandler}).
	 */
	function SentRecipients($result) {
		$SQL_result = Convert::raw2sql($result);
		return DataObject::get("Newsletter_SentRecipient",array("ParentID='".$this->ID."'", "Result='".$SQL_result."'"));
	}
	
	/**
	 * Returns a DataObjectSet containing the subscribers who have never been sent this Newsletter
	 *
	 */
	function UnsentSubscribers() {
		// Get a list of everyone who has been sent this newsletter
		$sent_recipients = DataObject::get("Newsletter_SentRecipient","ParentID='".$this->ID."'");
		// If this Newsletter has not been sent to anyone yet, $sent_recipients will be null
		if ($sent_recipients != null) {
			$sent_recipients_array = $sent_recipients->toNestedArray('MemberID');
		} else { 
			$sent_recipients_array = array();
		}

		// Get a list of all the subscribers to this newsletter
		$subscribers = DataObject::get( 'Member', "`GroupID`='".$this->Parent()->GroupID."'", null, "INNER JOIN `Group_Members` ON `MemberID`=`Member`.`ID`" );
		// If this Newsletter has no subscribers, $subscribers will be null
		if ($subscribers != null) {
			$subscribers_array = $subscribers->toNestedArray();
		} else { 
			$subscribers_array = array();
		}

		// Get list of subscribers who have not been sent this newsletter:
		$unsent_subscribers_array = array_diff_key($subscribers_array, $sent_recipients_array);

		// Create new data object set containing the subscribers who have not been sent this newsletter:
		$unsent_subscribers = new DataObjectSet();
		foreach($unsent_subscribers_array as $key => $data) {
			$unsent_subscribers->push(new ArrayData($data));
		}
	
		return $unsent_subscribers;	
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
		"SentRecipients" => "Newsletter_SentRecipient",
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

/**
 * Database record for recipients that have had the newsletter sent to them.
 * @package cms
 * @subpackage newsletter
 */ 
class Newsletter_SentRecipient extends DataObject {
	/**
	 * The DB schema for Newsletter_SentRecipient.
	 *
	 * ParentID is the the Newsletter
	 * Email and MemberID keep track of the recpients information
	 * Result has 4 possible values: "Sent", (mail() returned TRUE), "Failed" (mail() returned FALSE), 
	 * 	"Bounced" ({@see $email_bouncehandler}), or "BlackListed" (sending to is disabled).
	 */
	static $db = array(
		"ParentID" => "Int",
		"Email" => "Varchar(255)",
		"Result" => "Enum('Sent, Failed, Bounced, BlackListed', 'Sent')",
	);
	static $has_one = array(
		"Member" => "Member",
	);
}

/**
 * Single recipient of the newsletter
 * @package cms
 * @subpackage newsletter
 */
class Newsletter_Recipient extends DataObject {
	static $db = array(
		"ParentID" => "Int",
	);
	static $has_one = array(
		"Member" => "Member",
	);
}

/**
 * Email object for sending newsletters.
 * @package cms
 * @subpackage newsletter
 */
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