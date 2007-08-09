<?php

class FeedbackAdmin extends LeftAndMain {
	
	public function init() {
		parent::init();
		
		Requirements::javascript("cms/javascript/FeedbackAdmin_right.js");
	}
	
	public function Link($action = null) {
		return "admin/feedback/$action";
	}
	
	public function showtable($params) {
	    return $this->getLastFormIn($this->renderWith('FeedbackAdmin_right'));
	}
	
	public function EditForm() {
		$url = rtrim($_SERVER['REQUEST_URI'], '/');
		if(strrpos($url, '&')) {
			$url = substr($url, 0, strrpos($url, '&'));
		}
		$section = substr($url, strrpos($url, '/') + 1);
		
		if($section != 'accepted' && $section != 'unmoderated' && $section != 'spam') {
			$section = 'accepted';
		}
		
		if($section == 'accepted') {
			$filter = 'IsSpam=0 AND NeedsModeration=0';
		} else if($section == 'unmoderated') {
			$filter = 'NeedsModeration=1';
		} else {
			$filter = 'IsSpam=1';
		}
		
		$tableFields = array(
			"Name" => "Author",
			"Comment" => "Comment",
			"PageTitle" => "Page"
		);
		
		$popupFields = new FieldSet(
			new TextField("Name"),
			new TextareaField("Comment", "Comment")
		);
		
		$idField = new HiddenField('ID');
		$table = new CommentTableField($this, "Comments", "PageComment", $section, $tableFields, $popupFields, $filter);
		$table->setParentClass(false);
		
		$fields = new FieldSet($idField, $table);
		
		$actions = new FieldSet(
			new FormAction('deletemarked', 'Delete')
		);
		
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
}

?>