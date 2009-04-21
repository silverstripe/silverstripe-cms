<?php
/**
 * Special kind of ComplexTableField for managing comments.
 * @package cms
 * @subpackage comments
 */
class CommentTableField extends ComplexTableField {
	protected $template = "CommentTableField";
	protected $mode;
	
	function __construct($controller, $name, $sourceClass, $mode, $fieldList, $detailFormFields = null, $sourceFilter = "", $sourceSort = "Created", $sourceJoin = "") {
		$this->mode = $mode;
		
		Session::set('CommentsSection', $mode);
		
		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);
		
		$this->Markable = true;
		$this->setPageSize(15);
		
		// search
		$search = isset($_REQUEST['CommentSearch']) ? Convert::raw2sql($_REQUEST['CommentSearch']) : null;
		if(!empty($_REQUEST['CommentSearch'])) {
			$this->sourceFilter[] = "( `Name` LIKE '%$search%' OR `Comment` LIKE '%$search%')";
		}
	}
	
	function FieldHolder() {
		$ret = parent::FieldHolder();
		
		Requirements::javascript(CMS_DIR . '/javascript/CommentTableField.js');
		
		return $ret;
	}
	
	function Items() {
		$this->sourceItems = $this->sourceItems();
		
		if(!$this->sourceItems) {
			return null;
		}
		
		$pageStart = (isset($_REQUEST['ctf'][$this->Name()]['start']) && is_numeric($_REQUEST['ctf'][$this->Name()]['start'])) ? $_REQUEST['ctf'][$this->Name()]['start'] : 0;
		$this->sourceItems->setPageLimits($pageStart, $this->pageSize, $this->totalCount);
		
		$output = new DataObjectSet();
		foreach($this->sourceItems as $pageIndex=>$item) {
			$output->push(Object::create('CommentTableField_Item',$item, $this, $pageStart+$pageIndex));
		}
		return $output;
	}
	
	function spam() {
		if(!Permission::check('ADMIN')) {
			return false;
		}

		$this->methodName = "spam";
		
		$childId = Convert::raw2sql($_REQUEST['ctf']['childID']);

		if (is_numeric($childId)) {
			$comment = DataObject::get_by_id($this->sourceClass, $childId);
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
			}
		}
	}
	
	function ham() {
		if(!Permission::check('ADMIN')) {
			return false;
		}

		$this->methodName = "ham";
		
		$childId = Convert::raw2sql($_REQUEST['ctf']['childID']);

		if (is_numeric($childId)) {
			$comment = DataObject::get_by_id($this->sourceClass, $childId);
			if($comment) {
				$comment->IsSpam = false;
				$comment->NeedsModeration = false;
				$comment->write();
				
				if(SSAkismet::isEnabled()) {
					try {
						$akismet = new SSAkismet();
						$akismet->setCommentAuthor($comment->getField('Name'));
						$akismet->setCommentContent($comment->getField('Comment'));
						
						$akismet->submitHam();
					} catch (Exception $e) {
						// Akismet didn't work, most likely the service is down.
					}
				}
			}
		}
	}
	
	function approve() {
		if(!Permission::check('ADMIN')) {
			return false;
		}

		$this->methodName = "accept";
		
		$childId = Convert::raw2sql($_REQUEST['ctf']['childID']);

		if(is_numeric($childId)) {
			$childObject = DataObject::get_by_id($this->sourceClass, $childId);
			if($childObject) {
				$childObject->IsSpam = false;
				$childObject->NeedsModeration = false;
				$childObject->write();
			}
		}
	}
	
	function HasSpamButton() {
		return $this->mode == 'approved' || $this->mode == 'unmoderated';
	}
	
	function HasApproveButton() {
		return $this->mode == 'unmoderated';
	}
	
	function HasHamButton() {
		return $this->mode == 'spam';
	}

	function SearchForm() {
		$query = isset($_GET['CommentSearch']) ? $_GET['CommentSearch'] : null;
		
		$searchFields = new FieldGroup(
			new TextField('CommentSearch', _t('CommentTableField.SEARCH', 'Search'), $query),
			new HiddenField("ctf[ID]",'',$this->mode),
			new HiddenField('CommentFieldName','',$this->name)
		);
		
		$actionFields = new LiteralField('CommentFilterButton','<input type="submit" name="CommentFilterButton" value="'. _t('CommentTableField.FILTER', 'Filter') .'" id="CommentFilterButton"/>');
		
		$fieldContainer = new FieldGroup(
			$searchFields,
			$actionFields
		);
		
		return $fieldContainer->FieldHolder();
	}
}

/**
 * Single row of a {@link CommentTableField}
 * @package cms
 * @subpackage comments
 */
class CommentTableField_Item extends ComplexTableField_Item {
	function HasSpamButton() {
		return $this->parent()->HasSpamButton();
	}
	
	function HasApproveButton() {
		return $this->parent()->HasApproveButton();
	}
	
	function HasHamButton() {
		return $this->parent()->HasHamButton();
	}
	
	function SpamLink() {
		return Controller::join_links($this->Link(), "?methodName=spam");
	}
	
	function HamLink() {
		return Controller::join_links($this->Link(), "?methodName=ham");
	}
	
	function ApproveLink() {
		return Controller::join_links($this->Link(), "?methodName=approve");
	}
}

?>