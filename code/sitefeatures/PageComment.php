<?php

/**
 * @package cms
 * @subpackage comments
 */

/**
 * Represents a single comment on a page
 * @package cms
 * @subpackage comments
 */
class PageComment extends DataObject {
	static $db = array(
		"Name" => "Varchar",
		"Comment" => "Text",
		"IsSpam" => "Boolean",
		"NeedsModeration" => "Boolean"
	);

	static $has_one = array(
		"Parent" => "SiteTree",
	);
	
	static $casting = array(
		"RSSTitle" => "Varchar",
	);

	// Number of comments to show before paginating
	static $comments_per_page = 10;
	
	static $moderate = false;
	
	static $bbcode = false;
	
	/**
	 * Return a link to this comment
	 * @return string link to this comment.
	 */
	function Link() {
		return $this->Parent()->Link() . '#PageComment_'. $this->ID;
	}
	
	function ParsedBBCode(){
		$parser = new BBCodeParser($this->Comment);
		return $parser->parse();		
	}

	function DeleteLink() {
		if(Permission::check('CMS_ACCESS_CMSMain')) {
			return "PageComment/deletecomment/$this->ID";
		}
	}
	function deletecomment() {
		if(Permission::check('CMS_ACCESS_CMSMain')) {
			$comment = DataObject::get_by_id("PageComment", $this->urlParams['ID']);
			if($comment) {
				$comment->delete();
			}
		}
		
		if(Director::is_ajax()) {
			echo "";
		} else {
			Director::redirectBack();
		}
	}
	
	function SpamLink() {
		$member = Member::currentUser();
		if(Permission::check('CMS_ACCESS_CMSMain') && !$this->getField('IsSpam')) {
			return "PageComment/reportspam/$this->ID";
		}
	}
	
	function HamLink() {
		if(Permission::check('CMS_ACCESS_CMSMain') && $this->getField('IsSpam')) {
			return "PageComment/reportham/$this->ID";
		}
	}
	
	function ApproveLink() {
		if(Permission::check('CMS_ACCESS_CMSMain') && $this->getField('NeedsModeration')) {
			return "PageComment/approve/$this->ID";
		}
	}
	
	function SpamClass() {
		if($this->getField('IsSpam')) {
			return 'spam';
		} else if($this->getField('NeedsModeration')) {
			return 'unmoderated';
		} else {
			return 'notspam';
		}
	}
	
	function approve() {
		if(Permission::check('CMS_ACCESS_CMSMain')) {
			$comment = DataObject::get_by_id("PageComment", $this->urlParams['ID']);
			$comment->NeedsModeration = false;
			$comment->write();
			
			if(Director::is_ajax()) {
				echo $comment->renderWith('PageCommentInterface_singlecomment');
			} else {
				Director::redirectBack();
			}
		}
	}
	
	function reportspam() {
		if(SSAkismet::isEnabled()) {
			if(Permission::check('CMS_ACCESS_CMSMain')) {
				$comment = DataObject::get_by_id("PageComment", $this->urlParams['ID']);
				
				if($comment) {
					try {
						$akismet = new SSAkismet();
						$akismet->setCommentAuthor($comment->getField('Name'));
						$akismet->setCommentContent($comment->getField('Comment'));
						
						$akismet->submitSpam();
					} catch (Exception $e) {
						// Akismet didn't work, most likely the service is down.
					}
					
					if(SSAkismet::getSaveSpam()) {
						$comment->setField('IsSpam', true);
						$comment->write();
					} else {
						$comment->delete();
					}
				}
			}
				
			if(Director::is_ajax()) {
				if(SSAkismet::getSaveSpam()) {
					echo $comment->renderWith('PageCommentInterface_singlecomment');
				} else {
					echo '';
				}
			} else {
				Director::redirectBack();
			}
		}
	}
	
	function reportham() {
		if(SSAkismet::isEnabled()) {
			if(Permission::check('CMS_ACCESS_CMSMain')) {
				$comment = DataObject::get_by_id("PageComment", $this->urlParams['ID']);
				
				if($comment) {
					try {
						$akismet = new SSAkismet();
						$akismet->setCommentAuthor($comment->getField('Name'));
						$akismet->setCommentContent($comment->getField('Comment'));
						
						$akismet->submitHam();
					} catch (Exception $e) {
						// Akismet didn't work, most likely the service is down.
					}
					
					$comment->setField('IsSpam', false);
					$comment->write();
				}
			}
		
			if(Director::is_ajax()) {
				echo $comment->renderWith('PageCommentInterface_singlecomment');
			} else {		
				Director::redirectBack();
			}
		}
	}
	
	function RSSTitle() {
		return sprintf(
			_t('PageComment.COMMENTBY', "Comment by '%s' on %s", PR_MEDIUM, 'Name, Page Title'),
			Convert::raw2xml($this->Name),
			$this->Parent()->Title
		);
	}
	
	function rss() {
		$parentcheck = isset($_REQUEST['pageid']) ? "ParentID = " . (int) $_REQUEST['pageid'] : "ParentID > 0";
		$comments = DataObject::get("PageComment", "$parentcheck AND IsSpam=0", "Created DESC", "", 10);
		if(!isset($comments)) {
			$comments = new DataObjectSet();
		}
		
		$rss = new RSSFeed($comments, "home/", "Page comments", "", "RSSTitle", "Comment", "Name");
		$rss->outputToBrowser();
	}

	
	function PageTitle() {
		return $this->Parent()->Title;
	}
	
	static function enableModeration() {
		self::$moderate = true;
	}	

	static function moderationEnabled() {
		return self::$moderate;
	}
	
	static function enableBBCode() {
		self::$bbcode = true;
	}	

	static function bbCodeEnabled() {
		return self::$bbcode;
	}
	
}

?>
