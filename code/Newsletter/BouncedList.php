<?php
// @TODO Make it possible to disable sending to bounced emails by unchecking a box and delete the email from the mailing list by clicking the red "X"
class BouncedList extends TableListField {
    
    protected $nlType;
    
    function __construct( $name, $newsletterType ) {
		parent::__construct($name, "Email_BounceRecord", array("BounceEmail" => "Email address", "Created" => "Last bounce at", "BounceMessage" => "Reason:"), "", "Created");
		$this->Markable = true;
		$this->IsReadOnly = false;
		$this->setPermissions(array('edit', 'delete', 'add'));
        
        if( is_object( $newsletterType ) )
            $this->nlType = $newsletterType;
        else
            $this->nlType = DataObject::get_by_id( 'NewsletterType', $newsletterType );
    } 
    

	function sourceItems() {
		 $id = $this->nlType->GroupID;
		// @TODO Try to find way to show Firstname and Surname under a 'Username' heading";
		// Get a list of all bounces for all subscribers to this mailing list
		$bouncedSubscribers = DataObject::get( 'Email_BounceRecord', "`GroupID`='$id'", null, "INNER JOIN `Group_Members` USING(`MemberID`)" );

		// @TODO Find faster/elegenter way to do this. Probably some sort of SQL JOIN would work instead
		// If there are bounces logged for this Mailing List
		if ($bouncedSubscribers) {
			$bouncedSubscribersWithMemberID = new DataObjectSet();
			// Convert bounce to array so we can easily iterate through them
			$bouncedSubscribersArray = $bouncedSubscribers->toArray();
			// Iterate through each bounce record and add 'GroupID' to it (this is clumsy)
			foreach($bouncedSubscribersArray as $key => $bouncedSubscriberObject)
			{
				$bouncedSubscriberArray = $bouncedSubscriberObject->getAllFields();
				// Add MemberID to DataObjectSet so the removebouncedmember link will work
				$bouncedSubscriberArray['GroupID'] = $id;
				$bouncedSubscribersWithMemberID->push(new ArrayData($bouncedSubscriberArray));
			}
			return $bouncedSubscribersWithMemberID;
		} else {
			return null;
		}

	}

	/**
	 * Sets the template to be rendered with
	 */
	function FieldHolder() {
		return $this->renderWith('NewsletterAdmin_BouncedList');
	}

    function setController($controller) {
		$this->controller = $controller;
	}

	// Not needed now that we are extending TableListField instead of FormField
	/*
    function Entries() {
        
        $id = $this->nlType->GroupID;
        
        $bounceRecords = DataObject::get( 'Email_BounceRecord', "`GroupID`='$id'", null, "INNER JOIN `Group_Members` USING(`MemberID`)" );
        
        //user_error($id, E_USER_ERROR );
        
        if( !$bounceRecords )
            return null;
        
        foreach( $bounceRecords as $bounceRecord ) {        
			if( $bounceRecord ) {
				$bouncedUsers[] = new ArrayData( array( 
                    'Record' => $bounceRecord,
                    'Member' => DataObject::get_by_id( 'Member', $bounceRecord->MemberID )
                )); 
            }
        }
        
        return new DataObjectSet( $bouncedUsers );  
    }
	*/
} 
?>
