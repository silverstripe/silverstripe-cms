<?php
/**
 * Represents an interface for viewing and adding page comments
 * Create one, passing the page discussed to the constructor.  It can then be
 * inserted into a template.
 * @package cms
 * @subpackage comments
 */
class PageCommentInterface extends RequestHandlingData {
	static $url_handlers = array(
		'$Item!' => '$Item',
	);
	static $allowed_actions = array(
		'PostCommentForm',
	);
	
	protected $controller, $methodName, $page;
	
	/**
	 * @var boolean If this is true, you must be logged in to post a comment 
	 * (and therefore, you don't need to specify a 'Your name' field unless 
	 * your name is blank)
	 */
	static $comments_require_login = false;
	
	/**
	 * @var string If this is a valid permission code, you must be logged in 
	 * and have the appropriate permission code on your account before you can 
	 * post a comment.
	 */
	static $comments_require_permission = "";
	
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
	}
	
	function Link() {
		return Controller::join_links($this->controller->Link(), $this->methodName);
	}
	
	/**
	 * See @link PageCommentInterface::$comments_require_login
	 * @param boolean state The new state of this static field
	 */
	static function set_comments_require_login($state) {
		self::$comments_require_login = (boolean) $state;
	}
	
	/**
	 * See @link PageCommentInterface::$comments_require_permission
	 * @param string permission The permission to check against.
	 */
	static function set_comments_require_permission($permission) {
		self::$comments_require_permission = $permission;
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
	 * @return boolean true if this page comment form requires users to have a
	 * valid permission code in order to post (used to customize the error 
	 * message).
	 */
	function PostingRequiresPermission() {
		return self::$comments_require_permission;
	}
	
	function Page() {
		return $this->page;
	}
	
	function PostCommentForm() {
		Requirements::javascript('jsparty/behaviour.js');
		Requirements::javascript('jsparty/prototype.js');
		Requirements::javascript('jsparty/scriptaculous/effects.js');
		Requirements::javascript('cms/javascript/PageCommentInterface.js');
		
		
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
				
		
		if(MathSpamProtection::isEnabled()){
			$fields->push(new TextField("Math", sprintf(_t('PageCommentInterface.SPAMQUESTION', "Spam protection question: %s"), MathSpamProtection::getMathQuestion())));
		}				
		
		$fields->push(new TextareaField("Comment", _t('PageCommentInterface.YOURCOMMENT', "Comments")));
		
		$form = new PageCommentInterface_Form($this, "PostCommentForm", $fields, new FieldSet(
			new FormAction("postcomment", _t('PageCommentInterface.POST', 'Post'))
		));
		
		$form->loadDataFrom(array(
			"Name" => Cookie::get("PageCommentInterface_Name"),
		));
		
		return $form;
	}
	
	function Comments() {
		// Comment limits
		if(isset($_GET['commentStart'])) {
			$limit = (int)$_GET['commentStart'].",".PageComment::$comments_per_page;
		} else {
			$limit = "0,".PageComment::$comments_per_page;
		}
		
		$spamfilter = isset($_GET['showspam']) ? '' : 'AND IsSpam=0';
		$unmoderatedfilter = Permission::check('ADMIN') ? '' : 'AND NeedsModeration = 0';
		$comments =  DataObject::get("PageComment", "ParentID = '" . Convert::raw2sql($this->page->ID) . "' $spamfilter $unmoderatedfilter", "Created DESC", "", $limit);
		
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
	
}

/**
 * @package cms
 * @subpackage comments
 */
class PageCommentInterface_Form extends Form {
	function postcomment($data) {
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
		Cookie::set("PageCommentInterface_Name", $data['Name']);
		
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
		$comment->IsSpam = false;
		$comment->NeedsModeration = PageComment::moderationEnabled();
		$comment->write();
		
		if(Director::is_ajax()) {
			if($comment->NeedsModeration){
				echo _t('PageCommentInterface_Form.AWAITINGMODERATION', "Your comment has been submitted and is now awating moderation.");
			} else{
				echo $comment->renderWith('PageCommentInterface_singlecomment');
			}
		} else {		
			Director::redirectBack();
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
