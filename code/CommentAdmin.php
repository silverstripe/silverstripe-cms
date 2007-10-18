<?php

class CommentAdmin extends LeftAndMain {
	
	public function init() {
		parent::init();
		
		Requirements::javascript("cms/javascript/CommentAdmin_right.js");
	}
	
	public function Link($action = null) {
		return "admin/comments/$action";
	}
	
	public function showtable($params) {
	    return $this->getLastFormIn($this->renderWith('CommentAdmin_right'));
	}
	
	public function Section() {
		$url = rtrim($_SERVER['REQUEST_URI'], '/');
		if(strrpos($url, '&')) {
			$url = substr($url, 0, strrpos($url, '&'));
		}
		$section = substr($url, strrpos($url, '/') + 1);
		
		if($section != 'approved' && $section != 'unmoderated' && $section != 'spam') {
			$section = 'approved';
		}
		
		return $section;
	}
	
	public function EditForm() {
		$section = $this->Section();
		
		if($section == 'approved') {
			$filter = 'IsSpam=0 AND NeedsModeration=0';
			$title = "<h2>Approved Comments</h2>";
		} else if($section == 'unmoderated') {
			$filter = 'NeedsModeration=1';
			$title = "<h2>Comments Awaiting Moderation</h2>";
		} else {
			$filter = 'IsSpam=1';
			$title = "<h2>Spam</h2>";
		}
		
		$filter .= ' AND ParentID>0';
		
		$tableFields = array(
			"Name" => "Author",
			"Comment" => "Comment",
			"PageTitle" => "Page",
			"Created" => "Date Posted"
		);	
		
		$popupFields = new FieldSet(
			new TextField("Name"),
			new TextareaField("Comment", "Comment")
		);
		
		$idField = new HiddenField('ID', '', $section);
		$table = new CommentTableField($this, "Comments", "PageComment", $section, $tableFields, $popupFields, array($filter));
		$table->setParentClass(false);
		
		$fields = new FieldSet(new LiteralField("Title", $title), $idField, $table);
		
		$actions = new FieldSet();
		
		if($section == 'unmoderated') {
			$actions->push(new FormAction('acceptmarked', 'Accept'));
		}
		
		if($section == 'approved' || $section == 'unmoderated') {
			$actions->push(new FormAction('spammarked', 'Mark as spam'));
		}
		
		if($section == 'spam') {
			$actions->push(new FormAction('hammarked', 'Mark as not spam'));
		}
		
		$actions->push(new FormAction('deletemarked', 'Delete'));
		
		if($section == 'spam') {
			$actions->push(new FormAction('deleteall', 'Delete All'));
		}
		
		$form = new Form($this, "EditForm", $fields, $actions);
		
		return $form;
	}
	
	function deletemarked() {
			$numComments = 0;
			$folderID = 0;
			$deleteList = '';
	
			if($_REQUEST['Comments']) {
				foreach($_REQUEST['Comments'] as $commentid) {
					$comment = DataObject::get_one('PageComment', "`PageComment`.ID = $commentid");
					if($comment) {
						$comment->delete();
						$numComments++;
					}
				}
			} else {
				user_error("No comments in $commentList could be found!", E_USER_ERROR);
			}
		
			echo <<<JS
				$deleteList
				$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value);
				statusMessage("Deleted $numComments comments.");
JS;
	}
	
	function deleteall() {
		$numComments = 0;
		$spam = DataObject::get('PageComment', "PageComment.IsSpam=1");
		
		if($spam) {
			$numComments = $spam->Count();
			
			foreach($spam as $comment) {
				$comment->delete();
			}
		}
		
		echo <<<JS
				$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value);
				statusMessage("Deleted $numComments comments.");
JS;
		
	}
	
	function spammarked() {
			$numComments = 0;
			$folderID = 0;
			$deleteList = '';
	
			if($_REQUEST['Comments']) {
				foreach($_REQUEST['Comments'] as $commentid) {
					$comment = DataObject::get_one('PageComment', "`PageComment`.ID = $commentid");
					if($comment) {
						$comment->IsSpam = true;
						$comment->NeedsModeration = false;
						$comment->write();
						
						if(SSAkismet::isEnabled()) {
							try {
								$akismet = new SSAkismet();
								$akismet->setCommentAuthor($comment->getField('Name'));
								$akismet->setCommentContent($comment->getField('Comment'));
								
								$akismet->submitSpam();
							} catch (Exception $e) {
								// Akismet didn't work, most likely the service is down.
							}
						}
						$numComments++;
					}
				}
			} else {
				user_error("No comments in $commentList could be found!", E_USER_ERROR);
			}
		
			echo <<<JS
				$deleteList
				$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value);
				statusMessage("Marked $numComments comments as spam.");
JS;
	}
	
	function hammarked() {
			$numComments = 0;
			$folderID = 0;
			$deleteList = '';
	
			if($_REQUEST['Comments']) {
				foreach($_REQUEST['Comments'] as $commentid) {
					$comment = DataObject::get_one('PageComment', "`PageComment`.ID = $commentid");
					if($comment) {
						$comment->IsSpam = false;
						$comment->NeedsModeration = false;
						$comment->write();
						
						if(SSAkismet::isEnabled()) {
							try {
								$akismet = new SSAkismet();
								$akismet->setCommentAuthor($comment->getField('Name'));
								$akismet->setCommentContent($comment->getField('Comment'));
								
								$akismet->submitSpam();
							} catch (Exception $e) {
								// Akismet didn't work, most likely the service is down.
							}
						}
						
						$numComments++;
					}
				}
			} else {
				user_error("No comments in $commentList could be found!", E_USER_ERROR);
			}
		
			echo <<<JS
				$deleteList
				$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value);
				statusMessage("Marked $numComments comments as not spam.");
JS;
	}
	
	function approvemarked() {
			$numComments = 0;
			$folderID = 0;
			$deleteList = '';
	
			if($_REQUEST['Comments']) {
				foreach($_REQUEST['Comments'] as $commentid) {
					$comment = DataObject::get_one('PageComment', "`PageComment`.ID = $commentid");
					if($comment) {
						$comment->IsSpam = false;
						$comment->NeedsModeration = false;
						$comment->write();
						$numComments++;
					}
				}
			} else {
				user_error("No comments in $commentList could be found!", E_USER_ERROR);
			}
		
			echo <<<JS
				$deleteList
				$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value);
				statusMessage("Accepted $numComments comments.");
JS;
	}
}

?>