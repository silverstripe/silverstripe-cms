<?php

class CMSAsset extends CMSMain {
	
	/**
	 *
	 * @var string
	 */
	static $url_segment = 'assets';
	
	/**
	 *
	 * @var string
	 */
	static $url_rule = '/$Action/$ID';
	
	/**
	 *
	 * @var string
	 */
	public static $menu_title = 'Files';

	/**
	 *
	 * @var array
	 */
	static $allowed_actions = array(
		'deletefile'
	);
	
	/**
	 *
	 * @return Form 
	 */
	public function getEditForm() {
		
		$config = GridFieldConfig::create();
		
		// The default handler - provides columns based on gridField->displayFields;
		$config->addComponent(new GridFieldFilter());
		$config->addComponent(new GridFieldDefaultColumns());
		$config->addComponent(new GridFieldSortableHeader());
		$config->addComponent(new GridFieldPaginator(25));
		
		// Add the delete functionality
		$config->addComponent(new CMSAsset_Delete());
		
		$gridField = new GridField('File','Files', DataList::create('File'), $config);
		
		// These fields are displayed in the gridfield
		// @todo This might change to something more SS
		$gridField->setDisplayFields(array(
			'ID' => 'ID',
			'CMSThumbnail' => 'CMSThumbnail',
			'Parent.FileName' => 'Folder',
			'Title'=>'Title',
			'ClassName'=>'Type',
			'LastEdited'=>'Date',
			'Size'=>'Size',
		));
		
		// Demo for casting the Data to nice format
		$gridField->setFieldCasting(array('LastEdited' => 'Date->Nice'));
		// Demo for adding formating to the Title field
		$gridField->setFieldFormatting(array('Title' => '<strong>$Title</strong>'));
		// Return the form
		return new Form($this, 'EditForm', new FieldList($gridField), new FieldList());
	}
}

/**
 * This class is an GridField Component that add Delete action for Objects in the GridField
 * 
 */
class CMSAsset_Delete implements GridField_ColumnProvider, GridField_ActionProvider {
	
	/**
	 * Add a column 'Delete'
	 * 
	 * @param type $gridField
	 * @param array $columns 
	 */
	public function augmentColumns($gridField, &$columns) {
		$columns[] = 'DeleteAction';
	}
	
	/**
	 * Return any special attributes that will be used for FormField::createTag()
	 *
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 * @return array
	 */
	public function getColumnAttributes($gridField, $record, $columnName) {
		return array();
	}
	
	/**
	 * Add the title 
	 * 
	 * @param GridField $gridField
	 * @param string $columnName
	 * @return array
	 */
	public function getColumnMetadata($gridField, $columnName) {
		if($columnName == 'DeleteAction') {
			return array('title' => 'Delete');
		}
	}
	
	/**
	 * Which columns are handled by this component
	 * 
	 * @param type $gridField
	 * @return type 
	 */
	public function getColumnsHandled($gridField) {
		return array('DeleteAction');
	}
	
	/**
	 * Which GridField actions are this component handling
	 *
	 * @param GridField $gridField
	 * @return array 
	 */
	public function getActions($gridField) {
		return array('deleterecord');
	}
	
	/**
	 *
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 * @return string - the HTML for the column 
	 */
	public function getColumnContent($gridField, $record, $columnName) {
		$field = new GridField_Action($gridField, 'DeleteRecord'.$record->ID, "x", "deleterecord", array('RecordID' => $record->ID));
		$output = $field->Field();
		return $output;
	}
	
	/**
	 * Handle the actions and apply any changes to the GridField
	 *
	 * @param GridField $gridField
	 * @param string $actionName
	 * @param mixed $arguments
	 * @param array $data - form data
	 * @return void
	 */
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		$id = $arguments['RecordID'];
		$item = $gridField->getList()->byID($id);
		if(!$item) return;

		if($actionName == 'deleterecord') {
			$item->delete();
		}
	}
}