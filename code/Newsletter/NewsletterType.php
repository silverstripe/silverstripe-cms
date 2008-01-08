<?php 

/**
 * @package cms
 * @subpackage newsletter
 */

/**
 * Represents a type of newsletter, for example the weekly products update.
 * The NewsletterType is associated with a recipient list and a bunch of Newsletter objects, which are each either Sent or Draft.
 */
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

    /**
     * Get the fieldset to display in the administration section
     */
    function getCMSFields() {
       $group = null;
		if($this->GroupID) {
			$group = DataObject::get_one("Group", "ID = $this->GroupID");
        }
	
    	$fields = new FieldSet(
            new TextField("Title", "Newsletter Type"),
            new TextField("FromEmail", "Send newsletters from"),
            new TabSet("Root",
                new Tab("Drafts",
                    $draftList = new NewsletterList("Draft", $this, "Draft")
                ),
                new TabSet("Sent",
                    new Tab("Sent",
                        $sendList = new NewsletterList("Send", $this, "Send")
                    ),
                    new Tab("Unsubscribed",
                        $unsubscribedList = new UnsubscribedList("Unsubscribed", $this)    
                    ),
                    new Tab("Bounced",
                        $bouncedList = new BouncedList("Bounced", $this )
                    )
                )
            )
        );
        
        if($this->GroupID) {
            $fields->addFieldToTab('Root', 
                new TabSet("Recipients",
                    new Tab( "Recipients",
                        $recipients = new MemberTableField(
                            $this,
                            "Recipients", 
                            $group
                            )
                    ),
                    new Tab( "Import",
                        $importField = new RecipientImportField("ImportFile","Import from file", $group )
                    )
                )
            );
            
            $recipients->setController($this);
            $importField->setController($this);
            $importField->setTypeID( $this->ID );
        }
                
        $fields->addFieldToTab('Root', 
            new Tab("Template",
                $templates = new TemplateList("Template","Template", $this->Template, NewsletterAdmin::template_path())
            )
        );
        
        $draftList->setController($this);
        $sendList->setController($this);
        
        $templates->setController($this);
        $unsubscribedList->setController($this);
        $bouncedList->setController($this);
        
        $fields->push($idField = new HiddenField("ID"));
        $fields->push( new HiddenField( "executeForm", "", "TypeEditForm" ) );
        $idField->setValue($this->ID);
        
        return $fields;
    }
}
?>
