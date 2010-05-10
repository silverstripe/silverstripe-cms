<?php

class ManyManyPickerField extends ItemSetField {
	
	static $actions = array('Search' => 'dialog');
	static $item_actions = array('Remove');
	
	function __construct($parent, $name, $title=null, $options=null) {
		
		if (isset($options['SortColumn'])) {
			$this->SortColumn = $options['SortColumn'];
			$options['Sortable'] = true;
		}
		
		parent::__construct($name, $title, $options);
		
		$this->parent = $parent;
		
		list($parentClass, $componentClass, $parentField, $componentField, $table) = $parent->many_many($this->name);
		$this->joinTable = $table;
		$this->otherClass = ( $parentClass == $parent->class ) ? $componentClass : $parentClass;
	}
	
	function Items() {
		$accessor = $this->name;
		if ($this->SortColumn) return $this->parent->getManyManyComponents($accessor, '', "\"{$this->joinTable}\".\"{$this->SortColumn}\"");
		return $this->parent->$accessor();
	}
		
	function saveInto(DataObject $record) {
		$fieldName = $this->name;
		$saveDest = $record->$fieldName();
		
		if (!$saveDest) user_error("ManyManyPickerField::saveInto() Field '$fieldName' not found on $record->class.$record->ID", E_USER_ERROR);

		$saveDest->removeAll();
		if ($this->value) foreach ($this->value as $order => $id) $saveDest->add($id, $this->SortColumn ? array($this->SortColumn => $order) : null);	
	}
	
	function SearchForm() {
		$context = singleton($this->otherClass)->getDefaultSearchContext();
		$fields = $context->getSearchFields();

		$form = new Form($this, "SearchForm",
			$fields,
			new FieldSet(
				new FormAction('Search', _t('MemberTableField.SEARCH', 'Search')),
				new ResetFormAction('ClearSearch', _t('ModelAdmin.CLEAR_SEARCH','Clear Search'))
			)
		);
		$form->setFormMethod('get');
		
		return $form;
	}
	
	function ResultsForm($searchCriteria) {
		if($searchCriteria instanceof SS_HTTPRequest) $searchCriteria = $searchCriteria->getVars();
		
		$form = new Form($this, 'ResultsForm',
			new FieldSet(
				$searchfield = new ManyManyPickerField_SearchField($this)
			),
			new FieldSet()
		);
		$searchfield->setSearchCriteria($searchCriteria);
		
		return $form;
	}
	
	function Search($searchCriteria) {
		if($searchCriteria instanceof SS_HTTPRequest) $searchCriteria = $searchCriteria->getVars();

		$form = $this->SearchForm();
		$form->loadDataFrom($searchCriteria);

		$resform = $this->ResultsForm($searchCriteria);
		
		return $this->customise(array('Form' => $form, 'Results' => $resform))->renderWith('ManyManyPickerField_Search');
	}
	
	function Add($data, $item) {
		$accessor = $this->name;
		$this->parent->$accessor()->add($item);
		$this->parent->write();	
		
		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}
	
	function Remove($data, $item) {
		$accessor = $this->name;
		$this->parent->$accessor()->remove($item);
		$this->parent->write();	
		
		return Director::is_ajax() ? $this->FieldHolder() : Director::redirectBack();
	}
}

class ManyManyPickerField_SearchField extends SearchItemSetField {
	
	static $item_default_action = 'Choose';
	
	function __construct($parent) {
		$this->parent = $parent;
		parent::__construct($parent->otherClass, 'Results');
	}
	
	function Choose($data, $item) {
		return $this->parent->Add($data, $item);
	}
}
