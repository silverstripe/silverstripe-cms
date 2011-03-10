<?php
/**
 * Represents an interface for viewing and adding page comments
 * Create one, passing the page discussed to the constructor.  It can then be
 * inserted into a template.
 * @package cms
 * @subpackage comments
 */
class PageCommentInterface extends RequestHandler {
	static $url_handlers = array(
		'$Item!' => '$Item',
	);
	static $allowed_actions = array(
		'PostCommentForm',
	);
	
	protected $controller, $methodName, $page;
	
	/**
	 * If this is true, you must be logged in to post a comment 
	 * (and therefore, you don't need to specify a 'Your name' field unless 
	 * your name is blank)
	 * 
	 * @var bool
	 */
	static $comments_require_login = false;
	
	/**
	 * If this is a valid permission code, you must be logged in 
	 * and have the appropriate permission code on your account before you can 
	 * post a comment.
	 * 
	 * @var string 
	 */
	static $comments_require_permission = "";
	
	/**
	 * If this is true it will include the javascript for AJAX 
	 * commenting. If it is set to false then it will not load
	 * the files required and it will fall back
	 * 
	 * @var bool
	 */
	static $use_ajax_commenting = true;
	
	/**
	 * If this is true then we should show the existing comments on 
	 * the page even when we have disabled the comment form. 
	 *
	 * If this is false the form + existing comments will be hidden
	 * 
	 * @var bool
	 * @since 2.4 - Always show them by default
	 */
	static $show_comments_when_disabled = true;
	
	/**
	 * Define how you want to order page comments by. By default order by newest
	 * to oldest. 
	 * 
	 * @var String - used as $orderby in DB query
	 * @since 2.4 
	 */
	static $order_comments_by = "\"Created\" DESC";
	
	/**
	 * Create a new page comment interface
	 * @param controller The controller that the interface is used on
	 * @param methodName The method to return this PageCommentInterface object
	 * @param page The page that we're commenting on
	 */
	function __construct($controller, $methodName, $page) {
		$this->controller = $controller;
		$this->methodName = $methodName;
		$this->page = $page;
		parent::__construct();
	}
	
	function Link() {
		return Controller::join_links($this->controller->Link(), $this->methodName);
	}
	
	/**
	 * See {@link PageCommentInterface::$comments_require_login}
	 *
	 * @param boolean state The new state of this static field
	 */
	static function set_comments_require_login($state) {
		self::$comments_require_login = (boolean) $state;
	}
	
	/**
	 * See {@link PageCommentInterface::$comments_require_permission}
	 *
	 * @param string permission The permission to check against.
	 */
	static function set_comments_require_permission($permission) {
		self::$comments_require_permission = $permission;
	}
	
	/**
	 * See {@link PageCommentInterface::$show_comments_when_disabled}
	 * 
	 * @param bool - show / hide the existing comments when disabled
	 */
	static function set_show_comments_when_disabled($state) {
		self::$show_comments_when_disabled = $state;
	}
	
	/**
	 * See {@link PageCommentInterface::$order_comments_by}
	 *
	 * @param String
	 */
	static function set_order_comments_by($order) {
		self::$order_comments_by = $order;
	}
	
	/**
	 * See {@link PageCommentInterface::$use_ajax_commenting}
	 *
	 * @param bool
	 */
	static function set_use_ajax_commenting($state) {
		self::$use_ajax_commenting = $state;
	}
	
	function forTemplate() {
		return $this->renderWith('PageCommentInterface');
	}
	
	/**
	 * @return boolean true if the currently logged in user can post a comment,
	 * false if they can't. Users can post comments by default, enforce 
	 * security by using 
	 * @link PageCommentInterface::set_comments_require_login() and 
	 * @link {PageCommentInterface::set_comments_require_permission()}.
	 */
	static function CanPostComment() {
		$member = Member::currentUser();
		if(self::$comments_require_permission && $member && Permission::check(self::$comments_require_permission)) {
			return true; // Comments require a certain permission, and the user has the correct permission
		} elseif(self::$comments_require_login && $member && !self::$comments_require_permission) {
			return true; // Comments only require that a member is logged in
		} elseif(!self::$comments_require_permission && !self::$comments_require_login) {
			return true; // Comments don't require anything - anyone can add a comment
		}
		
		return false;
	}
	
	/**
	 * if this page comment form requires users to have a
	 * valid permission code in order to post (used to customize the error 
	 * message).
	 * 
	 * @return bool
	 */
	function PostingRequiresPermission() {
		return self::$comments_require_permission;
	}
	
	function Page() {
		return $this->page;
	}
	
	function PostCommentForm() {
		if(!$this->page->ProvideComments){ 
			return false;
		}
		$fields = new FieldSet(
			new HiddenField("ParentID", "ParentID", $this->page->ID)
		);
		
		$member = Member::currentUser();
		
		if((self::$comments_require_login || self::$comments_require_permission) && $member && $member->FirstName) {
			// note this was a ReadonlyField - which displayed the name in a span as well as the hidden field but
			// it was not saving correctly. Have changed it to a hidden field. It passes the data correctly but I 
			// believe the id of the form field is wrong.
			$fields->push(new ReadonlyField("NameView", _t('PageCommentInterface.YOURNAME', 'Your name'), $member->getName()));
			$fields->push(new HiddenField("Name", "", $member->getName()));
		} else {
			$fields->push(new TextField("Name", _t('PageCommentInterface.YOURNAME', 'Your name')));
		}
				
		// optional commenter URL
		$fields->push(new TextField("CommenterURL", _t('PageCommentInterface.COMMENTERURL', "Your website URL")));
		
		if(MathSpamProtection::isEnabled()){
			$fields->push(new TextField("Math", sprintf(_t('PageCommentInterface.SPAMQUESTION', "Spam protection question: %s"), MathSpamProtection::getMathQuestion())));
		}				
		
		$fields->push(new TextareaField("Comment", _t('PageCommentInterface.YOURCOMMENT', "Comments")));
		
		$form = new PageCommentInterface_Form($this, "PostCommentForm", $fields, new FieldSet(
			new FormAction("postcomment", _t('PageCommentInterface.POST', 'Post'))), new RequiredFields('Name', 'Comment'));
		
		// Set it so the user gets redirected back down to the form upon form fail
		$form->setRedirectToFormOnValidationError(true);
		
		// Optional Spam Protection.
		if(class_exists('SpamProtectorManager')) {
			SpamProtectorManager::update_form($form, null, array('Name' => 'author_name', 'CommenterURL' => 'author_url', 'Comment' => 'post_body'));
			self::set_use_ajax_commenting(false);
		}
		
		// Shall We use AJAX?
		if(self::$use_ajax_commenting) {
			Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
			Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
			Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/effects.js');
			Requirements::javascript(CMS_DIR . '/javascript/PageCommentInterface.js');
		}
		
		$this->extend('updatePageCommentForm', $form);
		
		// Load the users data from a cookie
		$cookie = Cookie::get('PageCommentInterface_Data');
		if($cookie) {
			$visibleFields = array();
			foreach($fields as $field) {
				if(!$field instanceof HiddenField) $visibleFields[] = $field->Name();
			}
			$form->loadDataFrom(unserialize($cookie), false, $visibleFields);
		}

		return $form;
	}
	
	function Comments() {
		// Comment limits
		$limit = array();
		$limit['start'] = isset($_GET['commentStart']) ? (int)$_GET['commentStart'] : 0;
		$limit['limit'] = PageComment::$comments_per_page;
		
		$spamfilter = isset($_GET['showspam']) ? '' : "AND \"IsSpam\" = 0";
		$unmoderatedfilter = Permission::check('CMS_ACCESS_CommentAdmin') ? '' : "AND \"NeedsModeration\" = 0";
		$order = self::$order_comments_by;
		$comments =  DataObject::get("PageComment", "\"ParentID\" = '" . Convert::raw2sql($this->page->ID) . "' $spamfilter $unmoderatedfilter", $order, "", $limit);
		
		if(is_null($comments)) {
			return;
		}
		
		// This allows us to use the normal 'start' GET variables as well (In the weird circumstance where you have paginated comments AND something else paginated)
		$comments->setPaginationGetVar('commentStart');
		
		return $comments;
	}
	
	function CommentRssLink() {
		return Director::absoluteBaseURL() . "PageComment/rss?pageid=" . $this->page->ID;
	}
	
	/**
	 * A link to PageComment_Controller.deleteallcomments() which deletes all
	 * comments on a page referenced by the url param pageid
	 */
	function DeleteAllLink() {
		if(Permission::check('CMS_ACCESS_CommentAdmin')) {
			$token = SecurityToken::inst();
			return $token->addToUrl(Director::absoluteBaseURL() . "PageComment/deleteallcomments?pageid=" . $this->page->ID);
		}
	}
	
}

/**
 * @package cms
 * @subpackage comments
 */
class PageCommentInterface_Form extends Form {
	function postcomment($data) {
		Cookie::set("PageCommentInterface_Data", serialize($data));

		// Spam filtering
		if(SSAkismet::isEnabled()) {
			try {
				$akismet = new SSAkismet();
				
				$akismet->setCommentAuthor($data['Name']);
				$akismet->setCommentContent($data['Comment']);
				
				if($akismet->isCommentSpam()) {
					if(SSAkismet::getSaveSpam()) {
						$comment = Object::create('PageComment');
						$this->saveInto($comment);
						$comment->setField("IsSpam", true);
						$comment->write();
					}
					echo "<b>"._t('PageCommentInterface_Form.SPAMDETECTED', 'Spam detected!!') . "</b><br /><br />";
					printf("If you believe this was in error, please email %s.", ereg_replace("@", " _(at)_", Email::getAdminEmail()));
					echo "<br /><br />"._t('PageCommentInterface_Form.MSGYOUPOSTED', 'The message you posted was:'). "<br /><br />";
					echo $data['Comment'];
					
					return;
				}
			} catch (Exception $e) {
				// Akismet didn't work, continue without spam check
			}
		}
		
		//check if spam question was right.
		if(MathSpamProtection::isEnabled()){
			if(!MathSpamProtection::correctAnswer($data['Math'])){
				if(!Director::is_ajax()) {				
					Director::redirectBack();
				}
				return "spamprotectionfailed"; //used by javascript for checking if the spam question was wrong
			}
		}
		
		// If commenting can only be done by logged in users, make sure the user is logged in
		$member = Member::currentUser();
		if(PageCommentInterface::CanPostComment() && $member) {
			$this->Fields()->push(new HiddenField("AuthorID", "Author ID", $member->ID));
		} elseif(!PageCommentInterface::CanPostComment()) {
			echo "You're not able to post comments to this page. Please ensure you are logged in and have an appropriate permission level.";
			return;
		}

		$comment = Object::create('PageComment');
		$this->saveInto($comment);
		
		// Store the Session ID if needed for Spamprotection
		if($session = Session::get('mollom_user_session_id')) {
			$comment->SessionID = $session;
			Session::clear('mollom_user_session_id');	
		}
		$comment->IsSpam = false;
		$comment->NeedsModeration = PageComment::moderationEnabled();
		$comment->write();
		
		unset($data['Comment']);
		Cookie::set("PageCommentInterface_Data", serialize($data));
		
		$moderationMsg = _t('PageCommentInterface_Form.AWAITINGMODERATION', "Your comment has been submitted and is now awaiting moderation.");
		
		if(Director::is_ajax()) {
			if($comment->NeedsModeration){
				echo $moderationMsg;
			} else{
				echo $comment->renderWith('PageCommentInterface_singlecomment');
			}
		} else {		
			if($comment->NeedsModeration){
				$this->sessionMessage($moderationMsg, 'good');
			}
			
			if($comment->ParentID) {
				$page = DataObject::get_by_id("Page", $comment->ParentID);
				if($page) {
					// if it needs moderation then it won't appear in the list. Therefore
					// we need to link to the comment holder rather than the individual comment
					$url = ($comment->NeedsModeration) ? $page->Link() . '#PageComments_holder' : $page->Link() . '#PageComment_' . $comment->ID;
					
					return Director::redirect($url);
				}
			}
			
			return Director::redirectBack();
		}
	}
}

/**
 * @package cms
 * @subpackage comments
 */
class PageCommentInterface_Controller extends ContentController {
	function __construct() {
		parent::__construct(null);
	}
	
	function newspamquestion() {
		if(Director::is_ajax()) {
			echo Convert::raw2xml(sprintf(_t('PageCommentInterface_Controller.SPAMQUESTION', "Spam protection question: %s"),MathSpamProtection::getMathQuestion()));
		}
	}
}

?>
