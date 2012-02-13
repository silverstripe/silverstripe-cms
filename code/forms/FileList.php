<?php
/**
 * A FormField showing a list of files
 * @package cms
 * @subpackage assets
 */
class FileList extends TableListField {
	// bdc: added sort by Title as default behaviour
	protected $folder;
	function __construct($name, $folder) {
		$this->folder = $folder;
		parent::__construct($name, "File", array("Title" => "Title", "LinkedURL" => "URL"), "", "Title");
		$this->Markable = true;
	}
	
	function sourceItems() {
		return DataObject::get("File", "\"ParentID\" = '" . $this->folder->ID . "' AND \"ClassName\" <> 'Folder'", '"Title"');
	}
}

