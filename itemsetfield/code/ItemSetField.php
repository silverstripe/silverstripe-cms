<?php

abstract class ItemSetField extends FormField {

	/* Actions that can be on the set as a whole */
	static $actions = array();
	
	/* Actions that can be performed per-item. For more programatic calculation, override ItemActions, which is always called rather than accessing this directly */
	static $item_actions = array();
	static $item_default_action = null;
	
	static $defaults = array(
		'Sortable' => false,
		'Pageable' => false,
		'DisplayAs' => 'list'
	);
	
	static $url_handlers = array(
		'item/$ItemID!' => 'handleItem',
		'$Action!' => '$Action',
		'' => 'FieldHolder'
	);
	
	function __construct($name, $title = null, $options = null) {
		parent::__construct($name, $title);

		$this->options = array_merge($options ? $options : array(), self::$defaults);

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui-1.8rc3.custom.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.sortable.js');		
		Requirements::css('itemsetfield/css/jquery.ui.smoothness/ui.all.css');
		Requirements::css('itemsetfield/css/itemsetfield.css');
		Requirements::javascript('itemsetfield/javascript/ItemSetField.js');
	}
		
	abstract function Items();
	
	/** The actions peformable on a given item. By default uses the static variable item_actions */
	function ItemActions($item) {
		$actions = new DataObjectSet();
		foreach ($this->stat('item_actions') as $name) $actions->push(new ItemSetField_Action($this, $name, $name));
		return $actions;
	}
	
	/** The default action when clicking on an item. By default uses the static variable item_default_action */
	function ItemDefaultAction($item) {
		if ($action = $this->stat('item_default_action')) return new ItemSetField_Action($this, $action, $action); 
		return null;
	}
	

	/** The fields needed for a given item. By default stores a hidden input to save ID with. */
	function ItemFields($item) {
		return new FieldSet(
			new HiddenField($this->name.'[]', null, $item->ID)
		);
	}
	
	function ItemClass() {
		switch ($this->options['DisplayAs']) {
			case 'list':
				return 'ItemSetField_ListItem';
			case 'table':
				return 'ItemSetField_TableItem';
			case 'block':
				return 'ItemSetField_BlockItem';
			default:
				user_error('Unknown ItemSet display type', E_USER_ERROR);
		}
	}

	function ItemForm($item) {
		$class = $this->ItemClass();
		return new $class($this, $item, $this->ItemFields($item), $this->ItemActions($item), $this->ItemDefaultAction($item));
	}
	
	function ItemForms() {
		$dos = new DataObjectSet() ;
		foreach ($this->Items() as $item) $dos->push($this->ItemForm($item));	
		
		return $dos;
	}
	
	function FieldHolder() {
		$templates = array();
		foreach (array_reverse(ClassInfo::ancestry($this)) as $class) {
			if ($class == 'FormField') break;
			$templates[] = $class;
		}
		
		return $this->renderWith($templates);
	}
	
	function Actions() {
		$actions = new DataObjectSet();
		foreach ($this->stat('actions') as $k => $v) {
			if (is_numeric($k)) $actions->push(new ArrayData(array('Name' => $v, 'Link' => Controller::join_links($this->Link(), $v))));
			else                $actions->push(new ArrayData(array('Name' => $k, 'Link' => Controller::join_links($this->Link(), $k), 'ExtraClass' => $v)));
		}
		
		return $actions;
	}
	
	function handleItem($req) {
		$item = $this->Items()->find('ID', $req->param('ItemID'));
		if ($item) return $this->ItemForm($item);
	}
}

class ItemSetField_Action extends ViewableData {
	function __construct($itemSet, $action, $name) {
		parent::__construct();
		
		$this->itemSet = $itemSet;
		$this->action = $action;
		$this->name = $name;
	}
	
	function setID($id) {
		$this->ID = $id;
	}
	
	function Link() {
		return Controller::join_links($this->itemSet->Link(), 'item', $this->ID, $this->action);
	}
	
	function Field() {
		return "<input type='button' class='item-set-field-action' rel='{$this->Link()}' value='{$this->name}' />";
	}
}

class ItemSetField_Item extends RequestHandler {
	function __construct($parent, $item, $fields = null, $actions = null, $defaultAction = null) {
		parent::__construct();
		
		$this->parent = $parent;
		$this->item = $item;
		
		$this->setFields($fields);
		$this->setActions($actions);
		$this->setDefaultAction($defaultAction);
	}
	
	static $url_handlers = array(
		'$Action!' => 'handleAction',
	);
	
	function handleAction($request) {
		$action = $request->param('Action');
		
		if (method_exists($this, $action) && $this->checkAccessAction($action)) {
			return $this->$action($request);
		}
		else if ($this->parent->checkAccessAction($action)) {
			return $this->parent->$action($request, $this->item);
		}
		
		return $this->httpError(403, "Action '$action' isn't allowed on class $this->class");
	}
	
	function getFields() {
		return $this->fields;
	}

	function setFields($fields) {
		$this->fields = $fields;
	}
	
	function setActions($actions) {
		$this->actions = $actions;
		if ($this->actions) foreach ($this->actions as $action) $action->setID($this->item->ID);
	}
	
	function getActions() {
		return $this->actions;
	}
	
	function setDefaultAction($action) {
		$this->defaultAction = $action;
		if ($this->defaultAction) $this->defaultAction->setID($this->item->ID);
	}
	
	function getDefaultAction() {
		return $this->defaultAction;
	}
	
	function forTemplate() {
		return $this->renderWith('ItemSetField_Item');
	}
}

class ItemSetField_ListItem extends ItemSetField_Item {
	
	function Label() {
		if (method_exists($this->item, 'Summary')) $summary = $this->item->Summary();  
		else {
			$summary = array();
			foreach ($this->item->summaryFields() as $field) $summary[] = ($this->item->XML_val($field)) ? $this->item->XML_val($field) : $this->item->$field; 
			$summary = implode(', ', $summary);
		}
		
		return $summary;
	}
}
