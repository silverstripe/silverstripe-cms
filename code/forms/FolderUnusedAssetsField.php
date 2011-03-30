<?php
/**
 * @package cms
 * @subpackage filesystem
 */
class Folder_UnusedAssetsField extends CompositeField {
	protected $folder;
	
	public function __construct($folder) {
		$this->folder = $folder;
		parent::__construct(new FieldSet());
	}
		
	public function getChildren() {
		if($this->children->Count() == 0) {
			$inlineFormAction = new InlineFormAction("delete_unused_thumbnails", _t('Folder.DELETEUNUSEDTHUMBNAILS', 'Delete unused thumbnails'));
			$inlineFormAction->includeDefaultJS(false) ;

			$this->children = new FieldSet(
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
	
	public function FieldHolder() {
		$output = "";
		foreach($this->getChildren() as $child) {
			$output .= $child->FieldHolder();
		}
		return $output;
	}


	/**
     * Creates table for displaying unused files.
     *
     * @returns AssetTableField
     */
	protected function getAssetList() {
		$where = $this->folder->getUnusedFilesListFilter();
        $assetList = new AssetTableField(
            $this->folder,
            "AssetList",
            "File", 
			array("Title" => _t('Folder.TITLE', "Title"), "LinkedURL" => _t('Folder.FILENAME', "Filename")), 
            "",
            $where
        );
		$assetList->setPopupCaption(_t('Folder.VIEWASSET', "View Asset"));
        $assetList->setPermissions(array("show","delete"));
        $assetList->Markable = false;
        return $assetList;
	}
}