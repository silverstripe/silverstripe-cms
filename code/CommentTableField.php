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
		
		// Note: These keys have special behaviour associated through TableListField.js
		$this->selectOptions = array(
			'all' => _t('CommentTableField.SELECTALL', 'All'),
			'none' => _t('CommentTableField.SELECTNONE', 'None')
		);
		
		// search
		$search = isset($_REQUEST['CommentSearch']) ? Convert::raw2sql($_REQUEST['CommentSearch']) : null;
		if(!empty($_REQUEST['CommentSearch'])) {
			$this->sourceFilter[] = "( \"Name\" LIKE '%$search%' OR \"Comment\" LIKE '%$search%')";
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
	
	function handleItem($request) {
		return new CommentTableField_ItemRequest($this, $request->param('ID'));
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
	
	/**
	 * @return String
	 */
	function SpamLink() {
		return Controller::join_links($this->Link(), "pagecommentaction", 'reportspam', $this->ID);
	}
	
	/**
	 * @return String
	 */
	function HamLink() {
		return Controller::join_links($this->Link(), "pagecommentaction", 'reportham', $this->ID);
	}
	
	/**
	 * @return String
	 */
	function ApproveLink() {
		return Controller::join_links($this->Link(), "pagecommentaction", 'approve', $this->ID);
	}
}

/**
 * @package cms
 * @subpackage comments
 */
class CommentTableField_ItemRequest extends ComplexTableField_ItemRequest {
	
	static $url_handlers = array(
		'pagecommentaction/$Action/$ID' => 'handlePageCommentAction',
	);
	
	/**
	 * @param SS_HTTPRequest $request
	 * @return SS_HTTPResponse
	 */
	function handlePageCommentAction($request) {
		$action = $request->param('Action');
		$whitelist = array('approve', 'reportspam', 'reportham');
		if(!in_array($action, $whitelist)) $this->httpError(403);
		
		$c = new PageComment_Controller($request);
		$c->init();
		return $c->$action($request);
	}
}
?>