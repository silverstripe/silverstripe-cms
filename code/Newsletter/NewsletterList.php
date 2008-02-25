<?php

/**
 * @package cms
 * @subpackage newsletter
 */

/**
 * Simple form field shown when the NewsletterAdmin first loads.
 * @package cms
 * @subpackage newsletter
 */
class NewsletterList extends FormField {
	function __construct($name, $mailtype, $status = "Draft") {
		if(is_object($mailtype)) $this->mailType = $mailtype;
		else $this->mailType = DataObject::get_by_id('NewsletterType',$mailtype);
		$this->status = $status;
		parent::__construct(null);
	}
	
	function FieldHolder() {
		return $this->renderWith("NewsletterList");
	}
	
	
	function setMailType($mailtype) {
		$this->mailType = $mailtype;
	}
	
	function setController($controller) {
		$this->controller = $controller;
	}
    /* No longer used
    function Newsletters() {
        return DataObject::get( 'Newsletter', "`ParentID`='{$this->mailType->ID}' AND `Status`='{$this->status}'" );   
    }
    */
    
	function DraftNewsletters() {
		return $this->mailType->DraftNewsletters();
	}
	
	function SentNewsletters() {
		return $this->mailType->SentNewsletters();
	}
	
	function Status() {
		return $this->status;
	}
	function mailTypeID() {
		return $this->mailType->ID;
	}
}

?>