<?php

class SearchItemSetField extends ItemSetField {
	
	function __construct($class, $name, $title=null, $options=null) {
		parent::__construct($name, $title, $options);
		$this->searchClass = $class;
	}
	
	function setSearchCriteria($searchCriteria) {
		$this->searchCriteria = $searchCriteria;
	}
	
	function Items() {
		if (!$this->searchCriteria) return null;
		
		$context = singleton($this->searchClass)->getDefaultSearchContext();
		$query = $context->getQuery($this->searchCriteria);
		
		$dos = new DataObjectSet();
		foreach ($query->execute() as $record) $dos->push(new $this->searchClass($record));
		
		return $dos;
	}
}
