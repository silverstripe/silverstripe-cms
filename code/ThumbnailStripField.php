<?php

/**
 * Provides a strip of thumbnails showing all of the images in the system.
 * It will be tied to a 'parent field' that will provide it with a filter by which to reduce the number
 * of thumbnails displayed.
 */
class ThumbnailStripField extends FormField {
	protected $parentField;
	protected $updateMethod;
	
	function __construct($name, $parentField, $updateMethod = "getimages") {
		$this->parentField = $parentField;
		$this->updateMethod = $updateMethod;
		
		parent::__construct($name);
	}
	
	function ParentField() {
		return $this->form->FormName() . '_' . $this->parentField;
	}
	
	function FieldHolder() {
		Requirements::javascript('cms/javascript/ThumbnailStripField.js');
		return $this->renderWith('ThumbnailStripField');
	}
	
	function Images() {
		//return DataObject::get("Image", "Paretn);
	}
	
	function UpdateMethod() {
		return $this->updateMethod;
	}
	
	/**
	 * Populate the Thumbnail strip field, by looking for a folder, 
	 * and the descendants of this folder.
	 */
	function getimages() {
		$result = '';
		$folder = DataObject::get_by_id("Folder", $_GET['folderID']);
		
		if( !$folder )
			return _t('ThumbnailStripField.NOTAFOLDER','This is not a folder');
		
		$folderList = $folder->getDescendantIDList("Folder");
	
		array_unshift($folderList, $folder->ID);
			
		$images = DataObject::get("Image", "ParentID IN (" . implode(', ', $folderList) . ")","Created");
		
		if($images) {
			$result .= '<ul>';
			foreach($images as $image) {
				$thumbnail = $image->getFormattedImage('StripThumbnail');
				
				// Constrain the output image to a 600x600 square.  This is passed to the destwidth/destheight in the class, which are then used to
				// set width & height properties on the <img> tag inserted into the CMS.  Resampling is done after save
				$width = $image->Width;
				$height = $image->Height;
				if($width > 600) {
					$height *= (600/$width);
					$width = 600;
				}
				if($height > 600) {
					$width *= (600/$height);
					$height = 600;
				}
				
				$result .= 
						'<li>' .
						'<a href=" ' . $image->Filename . '">' .
								'<img class="destwidth=' . round($width) . ',destheight=' . round($height) . '" src="'. $thumbnail->URL .'" alt="' . $image->Title . '" title="' . $image->Title .   '" />' .
						'</a>' .
						'</li>';
			}
			$result .= '</ul>';
		}else{
			$result =  "<h2> No images found in ". $folder->Title. "</h2>";
		}
		
		return $result;

	}

	function getflash() {

		$folder = DataObject::get_by_id("Folder", $_GET['folderID']);
		
		if( !$folder )
			return _t('ThumbnailStripField.NOTAFOLDER');
		
		$folderList = $folder->getDescendantIDList("Folder");
		array_unshift($folderList, $folder->ID);

		$width = Image::$strip_thumbnail_width - 10;
		$height = Image::$strip_thumbnail_height - 10;
		
		$flashObjects = DataObject::get("File", "ParentID IN (" . implode(', ', $folderList) . ") AND Filename LIKE '%.swf'");
		$result = '';		
		if($flashObjects) {
			$result .= '<ul>';
			foreach($flashObjects as $flashObject) {
				// doesn't work well because we can't stop/mute flash-files, AND IE does not bubble javascript-events
				// over a flash-object grml
//				$result .= <<<HTML
//<a href="$flashObject->URL">
//	<object type="application/x-shockwave-flash" data="$flashObject->URL" width="$width" height="$height">
//		<param name="movie" value="$flashObject->URL" />
//	</object>
//</a>
//HTML;
				$mceRoot = MCE_ROOT . 
				$result .= <<<HTML
<li>
<a href="$flashObject->URL">
	<img src="{$mceRoot}themes/advanced/images/spacer_flash.jpg" alt="spacer" width="$width" height="$height" />
	<br />
	$flashObject->Name
</a>
</li>
HTML;
			}
			$result .= '</ul>';			
		}
		
		return $result;

	}
}

?>