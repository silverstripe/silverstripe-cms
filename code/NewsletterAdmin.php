<?php

class NewsletterAdmin extends LeftAndMain {
	static $subitem_class = "Member";

	static $template_path = null; // defaults to (project)/templates/email

	public function init() {
		// Check permissions
		// if(!Member::currentUser() || !Member::currentUser()->isAdmin()) Security::permissionFailure($this);

		parent::init();
		/*
		if(!$this->can('AdminCMS')) {
			$messageSet = array(
				'default' => "Please choose an authentication method and enter your credentials to access the CMS.",
				'alreadyLoggedIn' => "I'm sorry, but you can't access that part of the CMS.  If you want to log in as someone else, do so below",
				'logInAgain' => "You have been logged out of the CMS.  If you would like to log in again, enter a username and password below.",
			);

			Security::permissionFailure($this, $messageSet);
			return;
		}*/

		Requirements::javascript(MCE_ROOT . "tiny_mce_src.js");
		Requirements::javascript("jsparty/tiny_mce_improvements.js");

		Requirements::javascript("jsparty/hover.js");
		Requirements::javascript("jsparty/scriptaculous/controls.js");

		Requirements::javascript("cms/javascript/SecurityAdmin.js");

        Requirements::javascript("cms/javascript/LeftAndMain_left.js");
		Requirements::javascript("cms/javascript/LeftAndMain_right.js");
        Requirements::javascript("cms/javascript/CMSMain_left.js");

		Requirements::javascript("cms/javascript/NewsletterAdmin_left.js");
		Requirements::javascript("cms/javascript/NewsletterAdmin_right.js");
		Requirements::javascript("sapphire/javascript/ProgressBar.js");

		// We don't want this showing up in every ajax-response, it should always be present in a CMS-environment
		if(!Director::is_ajax()) {
			Requirements::javascriptTemplate("cms/javascript/tinymce.template.js", array(
				"ContentCSS" => project() . "/css/editor.css",
				"BaseURL" => Director::absoluteBaseURL(),
				"Lang" => i18n::get_tinymce_lang()
			));
		}

		// needed for MemberTableField (Requirements not determined before Ajax-Call)
		Requirements::javascript("cms/javascript/MemberTableField.js");
		
		Requirements::css("cms/css/NewsletterAdmin.css");
	}

	public function remove() {
		$ids = explode( ',', $_REQUEST['csvIDs'] );

		$count = 0;
		foreach( $ids as $id ) {
			if( preg_match( '/^mailtype_(\d+)$/', $id, $matches ) )
				$record = DataObject::get_by_id( 'NewsletterType', $matches[1] );
			else if( preg_match( '/^[a-z]+_\d+_(\d+)$/', $id, $matches ) )
				$record = DataObject::get_by_id( 'Newsletter', $matches[1] );

			if($record) {
				$record->delete();
			}

			FormResponse::add("removeTreeNodeByIdx(\$('sitetree'), '$id' );");
			// Don't allow a deleted draft to be edited
			FormResponse::add("$('Form_EditForm').closeIfSetTo('$matches[1]');");
			$count++;
		}

		FormResponse::status_message('Deleted '.$count.' items','good');

		return FormResponse::respond();
	}

	public function getformcontent(){
		Session::set('currentPage', $_REQUEST['ID']);
		Session::set('currentType', $_REQUEST['type']);
		if($_REQUEST['otherID'])
			Session::set('currentOtherID', $_REQUEST['otherID']);
		SSViewer::setOption('rewriteHashlinks', false);
		$result = $this->renderWith($this->class . "_right");
		return $this->getLastFormIn($result);
	}

	/**
	* Called when a mailing list is clicked on the left menu
	*/
	public function showrecipients($params) {
		return $this->showWithEditForm( $params, $this->getMailingListEditForm( $params['ID'] ) );
	}

	/**
	* Called when a draft or sent newsletter is clicked on the left menu and when a new one is added
	*/
	public function shownewsletter($params) {
		return $this->showWithEditForm( $params, $this->getNewsletterEditForm( $params['ID'] ) );
	}

	/**
	* Called when a newsletter type is clicked on the left menu
	*/
	public function showmailtype($params) {
		return $this->showWithEditForm( $params, $this->getNewsletterTypeEditForm( $params['ID'] ) );
	}

	/**
	* Called when a 'Drafts' folder is clicked on the left menu
	*/
	public function showdrafts($params) {
		return $this->ShowNewsletterFolder($params, 'Draft');
	}

	/**
	* Called when a 'Sent Items' folder is clicked on the left menu
	*/
	public function showsent($params) {
		return $this->ShowNewsletterFolder($params, 'Sent');
	}

	/**
	* Shows either the 'Sent' or 'Drafts' folder using the NewsletterList template
	*/
	public function ShowNewsletterFolder($params, $type) {
		$id = $params['ID'];
		if(!is_numeric($id)) {
			$id = Session::get('currentPage');
		}
		if( is_a( $id, 'NewsletterType' ) ) {
				$mailType = $id;
				$id = $mailType->ID;
		} else {
			if($id && is_numeric($id)) {
				$mailType = DataObject::get_by_id( 'NewsletterType', $id );
			}
		}
		$draftList = new NewsletterList($type, $mailType, $type);
		return $draftList->renderWith("NewsletterList");
	}

    public function removenewsletter($params) {
        if( !is_numeric( $params['ID'] ) )
            return '';

        $newsletter = DataObject::get_by_id( 'Newsletter', $params['ID'] );
        $newsletter->delete();
        return 'letter-' . $params['ID'];
    }

    private function showWithEditForm( $params, $editForm ) {
        if(isset($params['ID'])) {
        	Session::set('currentPage', $params['ID']);
        }
		if(isset($params['OtherID'])) {
			Session::set('currentMember', $params['OtherID']);
		}
		if(Director::is_ajax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			return $editForm->formHtmlContent();
		} else {
			return array();
		}
    }

    public function getEditForm( $id ) {
        return $this->getNewsletterTypeEditForm( $id );
    }

    /**
     * Get the EditForm
     */
    public function EditForm() {
    	if((isset($_REQUEST['ID']) && isset($_REQUEST['Type']) && $_REQUEST['Type'] == 'Newsletter') || isset($_REQUEST['action_savenewsletter'])) {
    		return $this->NewsletterEditForm();
    	} else {
		// If a mailing list member is being added to a group, then call the Recipient form
		if (isset($_REQUEST['fieldName']) && 'Recipients' == $_REQUEST['fieldName']) {
			return $this->MailingListEditForm();
		} else {
			return $this->TypeEditForm();
		}
    	}
    }

    public function NewsletterEditForm() {
    	$id = $_REQUEST['ID'] ? $_REQUEST['ID'] : $this->currentPageID();
    	if(!is_numeric($id)) {
    		$id = 0;
    	}
    	return $this->getNewsletterEditForm($id);
    }

    public function TypeEditForm() {
    	$id = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : $this->currentPageID();
    	if(!is_numeric($id)) {
    		$id = 0;
    	}
    	return $this->getNewsletterTypeEditForm($id);
    }
    public function MailingListEditForm() {
    	$id = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : $this->currentPageID();
    	return $this->getMailingListEditForm($id);
    }

	public function getNewsletterTypeEditForm($id) {
        if(!is_numeric($id)) {
        	$id = Session::get('currentPage');
        }
	    if( is_a( $id, 'NewsletterType' ) ) {
	    		$mailType = $id;
	    		$id = $mailType->ID;
	    } else {
	    	if($id && is_numeric($id)) {
	    		$mailType = DataObject::get_by_id( 'NewsletterType', $id );
	    	}
	    }

		if(isset($mailType) && is_object($mailType) && $mailType->GroupID) {
			$group = DataObject::get_one("Group", "ID = $mailType->GroupID");
		}

		if(isset($mailType)) {
			$fields = new FieldSet(
				new TabSet("Root",
					new Tab("Newsletter Settings",
						new TextField("Title", _t('NewsletterAdmin.NEWSLTYPE','Newsletter Type')),
						new TextField("FromEmail", _t('NewsletterAdmin.FROMEM','From email address')),
						$templates = new TemplateList("Template","Template", $mailType->Template, self::template_path())
					)
				)
			);

			$templates->setController($this);

			$fields->push($idField = new HiddenField("ID"));
			$fields->push( new HiddenField( "executeForm", "", "TypeEditForm" ) );
			$idField->setValue($id);

			$actions = new FieldSet(new FormAction('save','Save'));

			$form = new Form($this, "EditForm", $fields, $actions);
			$form->loadDataFrom(array(
				'Title' => $mailType->Title,
				'FromEmail' => $mailType->FromEmail
			));
		} else {
			$form = false;
		}

		return $form;
	}
	
	public function getMailingListEditForm($id) {
        if(!is_numeric($id)) {
        		$id = Session::get('currentPage');
		}
	    if( is_a( $id, 'NewsletterType' ) ) {
	    		$mailType = $id;
	    		$id = $mailType->ID;
	    } else {
	    	if($id && is_numeric($id)) {
	    		$mailType = DataObject::get_by_id( 'NewsletterType', $id );
	    	}
	    }

		if(isset($mailType) && is_object($mailType) && $mailType->GroupID) {
			$group = DataObject::get_one("Group", "ID = $mailType->GroupID");
		}

		if(isset($mailType) && is_object($mailType)) {
			$fields = new FieldSet(
				new TabSet("Root",
					new Tab( "Recipients",
						$recipients = new MemberTableField(
							$this,
							"Recipients",
							$group
							)
					),
					new Tab( "Import",
						$importField = new RecipientImportField("ImportFile","Import from file", $group )
					),
					new Tab("Unsubscribers",
					$unsubscribedList = new UnsubscribedList("Unsubscribed", $mailType)
					),
					new Tab("Bounced", $bouncedList = new BouncedList("Bounced", $mailType )
					)
				)
			);

			$recipients->setController($this);
			$importField->setController($this);
	      		$unsubscribedList->setController($this);
			$bouncedList->setController($this);

			$importField->setTypeID( $id );

			$fields->push($idField = new HiddenField("ID"));
			$fields->push( new HiddenField( "executeForm", "", "MailingListEditForm" ) );
			$idField->setValue($id);
			// Save button is not used in Mailing List section
			$actions = new FieldSet(new HiddenField("save"));

			$form = new Form($this, "EditForm", $fields, $actions);
			$form->loadDataFrom(array(
				'Title' => $mailType->Title,
				'FromEmail' => $mailType->FromEmail
			));
		} else {
			$form = false;
		}

		return $form;

	}

	/**
	 * Removes a bounced member from the mailing list
	 *
	 * @return String
	 */
	function removebouncedmember($urlParams) {
		// First remove the Bounce entry	
		$id = Convert::raw2sql($urlParams['ID']);
		if (is_numeric($id)) {
			$bounceObject = DataObject::get_by_id('Email_BounceRecord', $id);
			if($bounceObject) {
				// Remove this bounce record
				$bounceObject->delete();
	
				$memberObject = DataObject::get_by_id('Member', $bounceObject->MemberID);
				$groupID = Convert::raw2sql($_REQUEST['GroupID']);
				if(is_numeric($groupID) && is_object($memberObject)) {
					// Remove the member from the mailing list
					$memberObject->Groups()->remove($groupID);
				} else {
					user_error("NewsletterAdmin::removebouncedmember: Bad parameters: Group=$groupID, Member=".$bounceObject->MemberID, E_USER_ERROR);
				}
				FormResponse::status_message($memberObject->Email.' was removed from the mailing list', 'good');
				FormResponse::add("$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value, 'recipients');");
				return FormResponse::respond();
			}
		} else {
			user_error("NewsletterAdmin::removebouncedmember: Bad parameters: Group=$groupID, Member=".$bounceObject->MemberID, E_USER_ERROR);
		}
	}

	/**
	 * Reloads the "Sent Status Report" tab via ajax
	 */
	function getsentstatusreport($params) {
		if(Director::is_ajax()) {
			$newsletter = DataObject::get_by_id( 'Newsletter', $params['ID'] );
			$sent_status_report = $newsletter->renderWith("Newsletter_SentStatusReport");
			return $sent_status_report;
		}
	}

	public static function template_path() {
		if(self::$template_path) return self::$template_path;
		else return self::$template_path = project() . '/templates/email';
	}
    
	/* Does not seem to be used 
	public function showdraft( $params ) {
        	return $this->showWithEditForm( $params, $this->getNewsletterEditForm( $params['ID'] ) );
	}
	*/

	public function getNewsletterEditForm($myId){

		$email = DataObject::get_by_id("Newsletter", $myId);
		if($email) {

			$fields = $email->getCMSFields($this);
			$fields->push($idField = new HiddenField("ID"));
			$idField->setValue($myId);
			$fields->push($ParentidField = new HiddenField("ParentID"));
			$ParentidField->setValue($email->ParentID);
			$fields->push($typeField = new HiddenField("Type"));
			$typeField->setValue('Newsletter');
			//$fields->push(new HiddenField("executeForm", "", "EditForm") );

			$actions = new FieldSet();

			if( $email->SentDate )
				$actions->push(new FormAction('send',_t('NewsletterAdmin.RESEND','Resend')));
			else
				$actions->push(new FormAction('send',_t('NewsletterAdmin.SEND','Send...')));

			$actions->push(new FormAction('save',_t('NewsletterAdmin.SAVE','Save')));

			$form = new Form($this, "EditForm", $fields, $actions);
			$form->loadDataFrom($email);

			if($email->Status != 'Draft') {
				$form->makeReadonly();
			}

			// user_error( $form->FormAction(), E_USER_ERROR );

			return $form;
		} else {
			user_error( 'Unknown Email ID: ' . $myId, E_USER_ERROR );
		}
	}

	public function SendProgressBar() {
		$progressBar = new ProgressBar( 'SendProgressBar', _t('NewsletterAdmin.SENDING','Sending emails...') );
		return $progressBar->FieldHolder();
	}

	public function sendnewsletter( /*$data, $form = null*/ ) {

		$id = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : $_REQUEST['NewsletterID'];

		if( !$id ) {
			FormResponse::status_message('No newsletter specified','bad');
			return FormResponse::respond();
		}

		$newsletter = DataObject::get_by_id("Newsletter", $id);
		$nlType = $newsletter->getNewsletterType();

		$e = new Newsletter_Email($nlType);
		$e->Body = $body = $newsletter->Content;
		$e->Subject = $subject = $newsletter->Subject;

		// TODO Make this dynamic

		if( $nlType && $nlType->FromEmail )
			$e->From = $from = $nlType->FromEmail;
		else
			$e->From = $from = Email::getAdminEmail();
			
		if(isset($_REQUEST['TestEmail'])) $e->To = $_REQUEST['TestEmail'];
		$e->setTemplate( $nlType->Template );

		$messageID = base64_encode( $newsletter->ID . '_' . date( 'd-m-Y H:i:s' ) );

		switch($_REQUEST['SendType']) {
			case "Test":
				if($_REQUEST['TestEmail']) {
					$e->To = $_REQUEST['TestEmail'];
					$e->setTemplate( $nlType->Template );

					self::sendToAddress( $e, $_REQUEST['TestEmail'], $messageID );
					FormResponse::status_message(_t('NewsletterAdmin.SENTTESTTO','Sent test to ') . $_REQUEST['TestEmail'],'good');
				} else {
					FormResponse::status_message(_t('NewsletterAdmin.PLEASEENTERMAIL','Please enter an email address'),'bad');
				}
			    break;
			case "List":
				// Send to the entire mailing list.
				$groupID = $nlType->GroupID;
				echo self::sendToList( $subject, $body, $from, $newsletter, $nlType, $messageID,
					DataObject::get( 'Member', "`GroupID`='$groupID'", null, "INNER JOIN `Group_Members` ON `MemberID`=`Member`.`ID`" )
				);
				break;
			case "Unsent":
				// Send to only those who have not already been sent this newsletter.
				$only_to_unsent = 1;
      				echo self::sendToList( $subject, $body, $from, $newsletter, $nlType, $messageID, $newsletter->UnsentSubscribers());
				break;
		}

		return FormResponse::respond();
	}


    static function sendToAddress( $email, $address, $messageID = null ) {
        $email->To = $address;
        $email->send();
    }

    static function sendToList( $subject, $body, $from, $newsletter, $nlType, $messageID = null, $recipients) {
        $emailProcess = new NewsletterEmailProcess( $subject, $body, $from, $newsletter, $nlType, $messageID, $recipients);
        return $emailProcess->start();
    }

	public function save($urlParams, $form) {
		// Both the Newsletter type and the Newsletter draft call save() when "Save" button is clicked
		if( isset($_REQUEST['Type']) && $_REQUEST['Type'] == 'Newsletter' )
			return $this->savenewsletter( $urlParams, $form );

		$id = $_REQUEST['ID'];
		$className = 'NewsletterType';
		$record = DataObject::get_one($className, "`$className`.ID = $id");
		
		// Is the template attached to the type, or the newsletter itself?

		$record->Template = addslashes( $_REQUEST['Template'] );

		$form->saveInto($record);
		$record->write();

		FormResponse::set_node_title("mailtype_$id", $record->Title);
		FormResponse::status_message(_t('NewsletterAdmin.SAVED','Saved'), 'good');
		$result = $this->getActionUpdateJS($record);
		return FormResponse::respond();
	}

	public function savenewsletter($urlParams, $form) {
		$id = $_REQUEST['ID'];

		$className = 'Newsletter';
		$record = DataObject::get_one($className, "`$className`.ID = $id");

		// Is the template attached to the type, or the newsletter itself?
		$type = $record->getNewsletterType();

		$record->Subject = $urlParams['Subject'];
		$record->Content = $urlParams['Content'];

		$record->write();

		$id = 'draft_'.$record->ParentID.'_'.$record->ID;

		FormResponse::set_node_title($id, $record->Title);
		FormResponse::status_message('Saved', 'good');
		// Get the new action buttons
		$actionList = '';
		foreach($form->Actions() as $action) {
			$actionList .= $action->Field() . ' ';
		}
		FormResponse::add("$('Form_EditForm').loadActionsFromString('" . Convert::raw2js($actionList) . "');");
		return FormResponse::respond();
	}

	/*
	 * Saves the settings on the 'Bounced' tab of the 'Mailing List' allowing members to be added to Email_BlackList
	 *
	 */
	public function memberblacklisttoggle($urlParams) {
		$id = $urlParams['ID'];
		$bounceObject = DataObject::get_by_id('Email_BounceRecord', $id);
		$memberObject = DataObject::get_by_id('Member', $bounceObject->MemberID);
		// If the email is currently not blocked, block it
		if (FALSE == $memberObject->BlacklistedEmail) {
			$memberObject->setBlacklistedEmail(TRUE);
			FormResponse::status_message($memberObject->Email.' was added to blacklist', 'good');
		} else {
			// Unblock the email
			$memberObject->setBlacklistedEmail(FALSE);
			FormResponse::status_message($memberObject->Email.' was removed from blacklist', 'good');
		}
		return FormResponse::respond();
	}

  function NewsletterAdminSiteTree() {
      return $this->getsitetree();
  }

  function getsitetree() {
      return $this->renderWith('NewsletterAdmin_SiteTree');
  }

	public function AddRecordForm() {
		$m = new MemberTableField($this,"Members", $this->currentPageID());
		return $m->AddRecordForm();
	}

	/**
	 * Ajax autocompletion
	 */
	public function autocomplete() {
		$fieldName = $this->urlParams['ID'];
		$fieldVal = $_REQUEST[$fieldName];

		$matches = DataObject::get("Member","$fieldName LIKE '" . addslashes($fieldVal) . "%'");
		if($matches) {
			echo "<ul>";
			foreach($matches as $match) {
				$data = $match->FirstName;
				$data .= ",$match->Surname";
				$data .= ",$match->Email";
				$data .= ",$match->Password";
				echo "<li>" . $match->$fieldName . "<span class=\"informal\">($match->FirstName $match->Surname, $match->Email)</span><span class=\"informal data\">$data</li>";
			}
			echo "</ul>";
		}
	}

	function savemember() {
		$data = $_REQUEST;

		$className = $this->stat('subitem_class');

		$id = $_REQUEST['ID'];
		if($id == 'new') $id = null;

		if($id) {
			$record = DataObject::get_one($className, "`$className`.ID = $id");
		} else {
            // send out an email to notify the user that they have been subscribed
			$record = new $className();
		}

		$record->update($data);
		$record->ID = $id;
		$record->write();

		$record->Groups()->add($data['GroupID']);

		$FirstName = Convert::raw2js($record->FirstName);
		$Surname = Convert::raw2js($record->Surname);
		$Email = Convert::raw2js($record->Email);
		$Password = Convert::raw2js($record->Password);
		$response = <<<JS
			$('MemberList').setRecordDetails($record->ID, {
				FirstName : "$FirstName",
				Surname : "$Surname",
				Email : "$Email"
			});
			$('MemberList').clearAddForm();
JS;
		FormResponse::add($response);
		FormResponse::status_message(_t('NewsletterAdmin.SAVED'), 'good');

		return FormResponse::respond();
	}


	public function NewsletterTypes() {
		return DataObject::get("NewsletterType","");
	}

	public function addgroup() {
		$parent = $_REQUEST['ParentID'] ? $_REQUEST['ParentID'] : 0;
		$p = new Group();
		$p->Title = "New Group";
		$p->Code = "new-group";
		$p->ParentID = $parent;
		$p->write();

		$this->returnItemToUser($p);
	}

	/**
	 * Called by AJAX to create a new newsletter type
	 *
	 */
	public function addtype( $params ) {
		$form = $this->getNewsletterTypeEditForm( $this->newNewsletterType() );

		return $this->showWithEditForm( $_REQUEST, $form );
	}

	/**
	 * Called by AJAX to create a new newsletter draft
	 *
	 */
	public function adddraft( $data) {
		$draftID = $this->newDraft( $_REQUEST['ParentID'] );
		// Needed for shownewsletter() to work
		$_REQUEST['ID'] = $draftID;
		return $this->shownewsletter($_REQUEST);
	}
	
    /**
    * Create a new newsletter type
    */
    private function newNewsletterType() {
        // create a new group for the newsletter
        $newGroup = new Group();
        $newGroup->Title = _t('NewsletterAdmin.NEWLIST','New mailing list');
        $newGroup->Code = "new-mailing-list";
        $newGroup->write();

        // create the new type
        $newsletterType = new NewsletterType();
        $newsletterType->Title = _t('NewsletterAdmin.NEWNEWSLTYPE','New newsletter type');
        $newsletterType->GroupID = $newGroup->ID;
        $newsletterType->write();

        // BUGFIX: Return only the ID of the new newsletter type
        return $newsletterType->ID;
    }

   private function newDraft( $parentID ) {
		if(!$parentID || !is_numeric( $parentID)) {
			$parent = DataObject::get_one("NewsletterType");
			if ($parent) {
				$parentID = $parent->ID;
			} else {
				// BUGFIX: It could be that no Newsletter types have been created, if so add one to prevent errors.
				$parentID = $this->newNewsletterType();
			}
		}
		if( $parentID && is_numeric( $parentID ) ) {
			$parent = DataObject::get_by_id("NewsletterType", $parentID);
			// BUGFIX: It could be that no Newsletter types have been created, if so add one to prevent errors.
			if(!$parent) {
				$parentID = $this->newNewsletterType();
			}
			$newsletter = new Newsletter();
			$newsletter->Status = 'Draft';
			$newsletter->Title = $newsletter->Subject = _t('NewsletterAdmin.MEWDRAFTMEWSL','New draft newsletter');
			$newsletter->ParentID = $parentID;
			$newsletter->write();
		} else {
			user_error( "You must first create a newsletter type before creating a draft", E_USER_ERROR );
		}

		return $newsletter->ID;
	}

	public function newmember() {
		Session::clear('currentMember');
		$newMemberForm = array(
			"MemberForm" => $this->getMemberForm('new'),
		);

		if(Director::is_ajax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			$customised = $this->customise($newMemberForm);
			$result = $customised->renderWith($this->class . "_rightbottom");
			$parts = split('</?form[^>]*>', $result);
			echo $parts[1];

		} else {
			return $newMemberForm;
		}
	}

	public function EditedMember() {
		if(Session::get('currentMember'))
			return DataObject::get_by_id("Member", Session::get('currentMember'));
	}

	public function Link($action = null) {
		if(!$action) $action = "index";
		return "admin/newsletter/$action/" . $this->currentPageID();
	}

	public function displayfilefield() {

		$id = $this->urlParams['ID'];

		return $this->customise( array( 'ID' => $id, "UploadForm" => $this->UploadForm() ) )->renderWith('Newsletter_RecipientImportField');
	}

	function UploadForm( $id = null ) {

		if( !$id )
			$id = $this->urlParams['ID'];

		$fields = new FieldSet(
			new FileField( "ImportFile", "" ),
			//new HiddenField( "action_import", "", "1" ),
			new HiddenField( "ID", "", $id )
		);

		$actions = new FieldSet(
			new FormAction( "action_import", _t('NewsletterAdmin.SHOWCONTENTS','Show contents') )
		);

		return new RecipientImportField_UploadForm( $this, "UploadForm", $fields, $actions );
	}
}

?>
