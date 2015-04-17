<?php
/**
 * @package cms
 * @subpackage filesystem
 */
class Folder_UnusedAssetsField extends CompositeField {
	
	/**
	 * @var Folder
	 */
	protected $folder;
	
	public function __construct($folder) {
		$this->folder = $folder;
		parent::__construct(new FieldList());
	}
		
	public function getChildren() {
		if($this->children->Count() == 0) {
			$inlineFormAction = new InlineFormAction("delete_unused_thumbnails", _t('Folder.DELETEUNUSEDTHUMBNAILS', 'Delete unused thumbnails'));
			$inlineFormAction->includeDefaultJS(false) ;

			$this->children = new FieldList(
				new LiteralField( "UnusedAssets", "<h2>"._t('Folder.UNUSEDFILESTITLE', 'Unused files')."</h2>" ),
				$this->getAssetList(),
				new FieldGroup(
					new LiteralField( "UnusedThumbnails", "<h2>"._t('Folder.UNUSEDTHUMBNAILSTITLE', 'Unused thumbnails')."</h2>"),
					$inlineFormAction
				)
			);
			$this->children->setForm($this->form);
		}
		return $this->children;
	}
	
	public function FieldHolder($properties = array()) {
		$output = "";
		foreach($this->getChildren() as $child) {
			$output .= $child->FieldHolder();
		}
		return $output;
	}


	/**
	 * Creates table for displaying unused files.
	 *
	 * @return GridField
	 */
	protected function getAssetList() {
		$where = $this->folder->getUnusedFilesListFilter();
		$files = File::get()->where($where);
		$field = new GridField('AssetList', false, $files);
		return $field;
	}
}
