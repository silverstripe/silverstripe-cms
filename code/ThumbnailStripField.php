<?php
/**
 * Provides a strip of thumbnails showing all of the images in the system.
 * It will be tied to a 'parent field' that will provide it with a filter by which to reduce the number
 * of thumbnails displayed.
 * @package cms
 * @subpackage assets
 */
class ThumbnailStripField extends FormField {
	protected $parentField;
	protected $updateMethod;

	/**
	 * If this is enabled, then ommitting a folderID when calling getimages will search ALL folders.
	 * This is the legacy behaviour.
	 *
	 * The new behaviour is to only search the root folder if a folderID is ommitted.
	 *
	 * @var boolean
	 */
	protected static $use_legacy_image_search = false;

	/**
	 * setter for ThumbnailStripField::use_legacy_image_search
	 * @param boolean $enable
	 */
	public static function set_use_legacy_image_search($enable) {
	    self::$use_legacy_image_search = $enable;
	}
	
	/**
	 * getter for ThumbnailStripField::use_legacy_image_search
	 * @return boolean
	 */
	public static function get_use_legacy_image_search() {
	    return self::$use_legacy_image_search;
	}
	
	function __construct($name, $parentField, $updateMethod = "getimages") {
		$this->parentField = $parentField;
		$this->updateMethod = $updateMethod;
		
		parent::__construct($name);
	}
	
	function ParentField() {
		return $this->form->FormName() . '_' . $this->parentField;
	}
	
	function FieldHolder() {
		Requirements::javascript(CMS_DIR . '/javascript/ThumbnailStripField.js');
		return $this->renderWith('ThumbnailStripField');
	}
	
	function UpdateMethod() {
		return $this->updateMethod;
	}
	
	/**
	 * Populate the Thumbnail strip field, by looking for a folder, 
	 * and the descendants of this folder.
	 *
	 * @see ThumbnailStripField::use_legacy_image_search
	 */
	function getimages() {
		$result = '';
		$images = null;
		$whereSQL = '';
		$folderID = isset($_GET['folderID']) ? (int) $_GET['folderID'] : 0;
		$searchText = (isset($_GET['searchText']) && $_GET['searchText'] != 'undefined' && $_GET['searchText'] != 'null') ? Convert::raw2sql($_GET['searchText']) : '';

		$folder = DataObject::get_by_id('Folder', (int) $_GET['folderID']);
		
		if($folder) {
			$folderList = $folder->getDescendantIDList();
			array_unshift($folderList, $folder->ID);

			$whereSQL = '"ParentID" IN (' . implode(', ', $folderList) . ')';
			if($searchText) $whereSQL .= " AND \"Filename\" LIKE '%$searchText%'";
			$images = DataObject::get('Image', $whereSQL, 'Title');
			
		} else {
			if (!self::$use_legacy_image_search) {
			    $whereSQL = "\"ParentID\" = 0";
			} else {
			    $whereSQL = '';

				// Prevent image search queries all images in the site initially when the page is loaded
				if(empty($searchText)) $whereSQL = "\"ParentID\" = 0";
			}
			
			if($searchText) {
				if (!empty($whereSQL)) $whereSQL .= " AND";
				$whereSQL .= " \"Filename\" LIKE '%$searchText%'";
			}
			
			$images = DataObject::get('Image', $whereSQL, 'Title');
		}
		
		if($images) {
			$result .= '<ul>';
			foreach($images as $image) {
				$thumbnail = $image->getFormattedImage('StripThumbnail');
				
				if ($thumbnail instanceof Image_Cached) {       //Hack here...
					// Constrain the output image to a 600x600 square.  This is passed to the destwidth/destheight in the class, which are then used to
					// set width & height properties on the <img> tag inserted into the CMS.  Resampling is done after save
					$width = $image->Width;
					$height = $image->Height;
					if($width > 600) {
						$height *= (600 / $width);
						$width = 600;
					}
					if($height > 600) {
						$width *= (600 / $height);
						$height = 600;
					}
					
					$result .= 
						'<li>' .
							'<a href="' . $image->Filename . '?r=' . rand(1,100000) . '" title="' . $image->Title .   '">' .
								'<img class="destwidth=' . round($width) . ',destheight=' . round($height) . '" src="'. $thumbnail->URL . '?r=' . rand(1,100000) . '" alt="' . $image->Title . '" />' .
							'</a>' .
						'</li>';
				}
			}
			$result .= '</ul>';
		} 
		else {
			if($folder) {
				$result = '<h2>' . _t('ThumbnailStripField.NOFOLDERIMAGESFOUND', 'No images found in') . ' ' . $folder->Title . '</h2>';
			} else {
				$result = '<h2>' . _t('ThumbnailStripField.NOIMAGESFOUND', 'No images found') . '</h2>';
			}
		}
		
		return $result;
	}

	function getflash() {
		$flashObjects = null;
		$result = '';
		$whereSQL = '';
		$folderID = isset($_GET['folderID']) ? (int) $_GET['folderID'] : 0;
		$searchText = (isset($_GET['searchText']) && $_GET['searchText'] != 'undefined' && $_GET['searchText'] != 'null') ? Convert::raw2sql($_GET['searchText']) : '';

		$width = Image::$strip_thumbnail_width - 10;
		$height = Image::$strip_thumbnail_height - 10;
		
		$folder = DataObject::get_by_id("Folder", (int) $_GET['folderID']);
		
		if($folder) {
			$folderList = $folder->getDescendantIDList();
			array_unshift($folderList, $folder->ID);
			
			$whereSQL = "\"ParentID\" IN (" . implode(', ', $folderList) . ") AND \"Filename\" LIKE '%.swf'";
			if($searchText) $whereSQL .= " AND \"Filename\" LIKE '%$searchText%'";
			
			$flashObjects = DataObject::get('File', $whereSQL);
		} else {
			if($searchText) {
				$flashObjects = DataObject::get('File', "\"Filename\" LIKE '%$searchText%' AND \"Filename\" LIKE '%.swf'");
			}
		}
		
		if($flashObjects) {
			$result .= '<ul>';
			foreach($flashObjects as $flashObject) {
				$result .= <<<HTML
<li>
<a href="$flashObject->URL" title="$flashObject->Title">
	<img src="cms/images/flash_small.jpg" alt="spacer" />
	<br />
	$flashObject->Name
</a>
</li>
HTML;
			}
			$result .= '</ul>';			
		} else {
			if($folder) {
				$result = '<h2>' . _t('ThumbnailStripField.NOFOLDERFLASHFOUND', 'No flash files found in') . ' ' . $folder->Title . '</h2>';
			} else {
				$result = '<h2>' . _t('ThumbnailStripField.NOFLASHFOUND', 'No flash files found') . '</h2>';
			}
		}
		
		return $result;
	}
}

?>