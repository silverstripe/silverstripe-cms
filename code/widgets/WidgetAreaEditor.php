<?php
/**
 * Special field type for selecting and configuring widgets on a page.
 * @package cms
 * @subpackage content
 */
class WidgetAreaEditor extends FormField {
	/**
	 * 3 variables to hold titles for the template
	 */
	public $InUseTitle;
	public $AvailableTitle;
	public $ToAddTitle;

	function __construct($name, $widgetClasses = array('Widget'), $maxWidgets = 0) {
		$this->MaxWidgets = $maxWidgets;
		$this->widgetClasses = $widgetClasses;
		
		parent::__construct($name);
	}
	
	function FieldHolder() {
		Requirements::css(CMS_DIR . '/css/WidgetAreaEditor.css');
		Requirements::javascript(CMS_DIR . '/javascript/WidgetAreaEditor.js');

		return $this->renderWith("WidgetAreaEditor");
	}
	
	function AvailableWidgets() {
		
		$widgets= new DataObjectSet();
		
		foreach($this->widgetClasses as $widgetClass) {
			$classes = ClassInfo::subclassesFor($widgetClass);
			array_shift($classes);
			foreach($classes as $class) {
				$widgets->push(singleton($class));
			}
		}
		
		return $widgets;
	}
	
	function UsedWidgets() {
		// Call class_exists() to load Widget.php earlier and avoid a segfault
		class_exists('Widget');
		
		$relationName = $this->name;
		$widgets = $this->form->getRecord()->getComponent($relationName)->Items();
		return $widgets;
	}
	
	function IdxField() {
		return $this->id() . 'ID';
	}
	
	function Value() {
		$relationName = $this->name;
		return $this->form->getRecord()->getComponent($relationName)->ID;
	}
	
	function saveInto(DataObject $record) {
		$name = $this->name;
		$idName = $name . "ID";

		$widgetarea = $record->getComponent($name);
		$widgetarea->write();
		
		$record->$idName = $widgetarea->ID;
	
		$widgets = $widgetarea->Items();
	
		// store the field IDs and delete the missing fields
		// alternatively, we could delete all the fields and re add them
		$missingWidgets = array();
		
		if($widgets) {
			foreach($widgets as $existingWidget) {
				$missingWidgets[$existingWidget->ID] = $existingWidget;
			}
		}
		
		if(isset($_REQUEST['Widget'])) {
			foreach(array_keys($_REQUEST['Widget']) as $widgetAreaName) {
				if ($widgetAreaName !== $this->name) {
					continue;
				}

				foreach(array_keys($_REQUEST['Widget'][$widgetAreaName]) as $newWidgetID) {
					$newWidgetData = $_REQUEST['Widget'][$widgetAreaName][$newWidgetID];

					// Sometimes the id is "new-1" or similar, ensure this doesn't get into the query
					if(!is_numeric($newWidgetID)) {
						$newWidgetID = 0;
					}
				
					// \"ParentID\" = '0' is for the new page
			  		$widget = DataObject::get_one(
						'Widget',
						"(\"ParentID\" = '{$record->$name()->ID}' OR \"ParentID\" = '0') AND \"Widget\".\"ID\" = '$newWidgetID'"
					);

		  		
			  		// check if we are updating an existing widget
					if($widget && isset($missingWidgets[$widget->ID])) {
			  			unset($missingWidgets[$widget->ID]);
					}
					
			  		// create a new object
			  		if(!$widget && !empty($newWidgetData['Type']) && class_exists($newWidgetData['Type'])) {
			  			$widget = new $newWidgetData['Type']();
			  			$widget->ID = 0;
			  			$widget->ParentID = $record->$name()->ID;

			  			if(!is_subclass_of($widget, 'Widget')) {
			  				$widget = null;
			  			}
			  		}
		  		
					if($widget) {
						if($widget->ParentID == 0) {
							$widget->ParentID = $record->$name()->ID;
						}
						// echo "Saving $widget->ID into $name/$widget->ParentID\n<br/>";
						$widget->populateFromPostData($newWidgetData);
					}
				}
			}
		}
		
		// remove the fields not saved
		if($missingWidgets) {
			foreach($missingWidgets as $removedWidget) {
				if(isset($removedWidget) && is_numeric($removedWidget->ID)) {
					$removedWidget->delete();
				}
			}
		}
	}
}