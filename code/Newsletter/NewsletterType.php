<?php 

class NewsletterType extends DataObject {

	static $db = array(
		"Title" => "Varchar",
		"Template" => "Varchar",
    	"FromEmail" => "Varchar",
    	"Sent" => "Datetime"
	);
	static $has_one = array(
		"Parent" => "SiteTree",
		"Group" => "Group",
	);
	static $has_many = array(
		"Newsletters" => "Newsletter",
	);
	
	function DraftNewsletters() {
		return DataObject::get("Newsletter","ParentID={$this->ID} AND Status ='Draft'");
	}
	
	function SentNewsletters() {
		return DataObject::get("Newsletter","ParentID={$this->ID} AND Status ='Send'");
	}
	
	function Recipients() {
		return DataObject::get("Member", "Group_Members.GroupID = {$this->GroupID}", "", "JOIN Group_Members on Group_Members.MemberID = Member.ID");
	}
	
	function delete() {
		foreach( $this->Newsletters() as $newsletter )
			$newsletter->delete();
			
		parent::delete();
	}
	
	/** 
	 * Updates the group so the security section is also in sync with
	 * the curent newsletters.
	 */
	function onBeforeWrite(){
		if($this->ID){
			$group = $this->Group();
			if($group->Title != "$this->Title"){
				$group->Title = "Mailing List: " . $this->Title;	
				// Otherwise the code would have mailing list in it too :-(
				$group->Code = SiteTree::generateURLSegment($this->Title);
				$group->write();
			}
		}
		parent::onBeforeWrite();
	}
}
?>
