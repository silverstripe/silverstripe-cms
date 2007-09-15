<?php
class NewsletterEmailProcess extends BatchProcess {
	
	protected $subject;
	protected $body;
	protected $from;
	protected $newsletter;
	protected $nlType;
	protected $messageID;
	
	function __construct( $subject, $body, $from, $newsletter, $nlType, $messageID = null ) {
		
		$this->subject = $subject;
		$this->body = $body;
		$this->from = $from;
		$this->newsletter = $newsletter;
		$this->nlType = $nlType;
		$this->messageID = $messageID;
		
        parent::__construct( $nlType->Recipients() );
	
	}
	
	function next( $count = 10 ) {
		$max = $this->current + $count;
		
		$max = $max < count( $this->objects ) ? $max : count( $this->objects );
		
		while($this->current < $max) {
			$index = $this->current++;
			$member = $this->objects[$index];
			
	        // check to see if the user has unsubscribed from the mailing list
	        // TODO Join in the above query first
	        $unsubscribeRecord = DataObject::get_one('Member_UnsubscribeRecord', "`MemberID`='{$member->ID}' AND `NewsletterTypeID`='{$this->nlType->ID}'");
	        
	        if( !$unsubscribeRecord ) {
	        	
	    		$address = $member->Email;   
	    		
	    		/**
	    		 * Email Blacklisting Support
	    		 */
	    		if($member->BlacklistedEmail && Email_BlackList::isBlocked($this->to)){
		    		 $bounceRecord = new Email_BounceRecord();
		    		 $bounceRecord->BounceEmail = $member->Email;
		    		 $bounceRecord->BounceTime = date("Y-m-d H:i:s",time());
		    		 $bounceRecord->BounceMessage = "BlackListed Email";
		    		 $bounceRecord->MemberID = $member->ID;
		    		 $bounceRecord->write();
		    		 continue;
		    	}
	    
	    		$e = new Newsletter_Email($this->nlType);
				$e->setBody( $this->body );
				$e->setSubject( $this->subject );
				$e->setFrom( $this->from );
				$e->setTemplate( $this->nlType->Template );
	
	    	
	    		$e->populateTemplate( array( 'Member' => $member, 'FirstName' => $member->FirstName ) );
	            $this->sendToAddress( $e, $address, $this->messageID );
	        }
    	}
    
	    if( $this->current >= count( $this->objects ) )
	    	return $this->complete();
	    else	
	    	return parent::next();
	}
	
	private function sendToAddress( $email, $address, $messageID = null ) {
	    $email->setTo( $address );
	    $email->send( $messageID );    
  }
	
	function complete() {
		parent::complete();
		
		if( $this->newsletter->SentDate ) {
			$resent = true;
		} else {
			$resent = false;
		}
    
    $this->newsletter->SentDate = 'now';
    $this->newsletter->Status = 'Send';
    $this->newsletter->write();
    
    $message = "statusMessage('Sent " . count( $this->objects ) . " emails successfully','good');";
	
	if( $resent )
    	return $message."resent_ok( '{$this->nlType->ID}', '{$this->newsletter->ID}' )";
    else    
      return $message."draft_sent_ok( '{$this->nlType->ID}', '{$this->newsletter->ID}' )";
	}
}
?>