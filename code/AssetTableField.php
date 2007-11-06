<?php
class AssetTableField extends ComplexTableField {
	
	protected $folder;
	
	protected $template = "AssetTableField";
	
	protected $permissions = array(
        "edit",
        "delete",
        //"export",
    );
	function __construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {
		
		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);

		$this->sourceSort = "Title";		
		$this->Markable = true;

	}
	
	function setFolder($folder) {
		$this->folder = $folder;
		$this->sourceFilter .= ($this->sourceFilter) ? " AND " : "";
		$this->sourceFilter .= " ParentID = '" . $folder->ID . "' AND ClassName <> 'Folder'";
	}
	
	function Folder() {
		return $this->folder;
	}
	
	function sourceID() {
		if($this->folder) return $this->folder->ID;
	}
	
	function DetailForm() {
		$ID = (isset($_REQUEST['ctf']['ID'])) ? Convert::raw2xml($_REQUEST['ctf']['ID']) : null;
		$childID = (isset($_REQUEST['ctf']['childID'])) ? Convert::raw2xml($_REQUEST['ctf']['childID']) : null;
		$childClass = (isset($_REQUEST['fieldName'])) ? Convert::raw2xml($_REQUEST['fieldName']) : null;
		$methodName = (isset($_REQUEST['methodName'])) ? $_REQUEST['methodName'] : null;
		
		if(!$childID) {
			user_error("AssetTableField::DetailForm Please specify a valid ID");
			return null;
		}
		
		if($childID) {
			$childData = DataObject::get_by_id("File", $childID);
		}
		
		if(!$childData) {
			user_error("AssetTableField::DetailForm No record found");
			return null;
		}
		
		if($childData->ParentID) {
			$folder = DataObject::get_by_id('File', $childData->ParentID );
		} else {
			$folder = singleton('Folder');
		}
		
		$urlLink = "<div class='field readonly'>";
		$urlLink .= "<label class='left'>URL</label>";
		$urlLink .= "<span class='readonly'><a href='{$childData->Link()}'>{$childData->RelativeLink()}</a></span>";
		$urlLink .= "</div>";
		
		$detailFormFields = new FieldSet(
			new TabSet("BottomRoot",
				new Tab("Main",
					new TextField("Title", _t('AssetTableField.TITLE','Title')),
					new TextField("Name", _t('AssetTableField.FILENAME','Filename')),
					new LiteralField("AbsoluteURL", $urlLink),
					new ReadonlyField("FileType", _t('AssetTableField.TYPE','Type')),
					new ReadonlyField("Size", _t('AssetTableField.SIZE','Size'), $childData->getSize()),
					new DropdownField("OwnerID", _t('AssetTableField.OWNER','Owner'), Member::mapInCMSGroups( $folder->CanEdit() ) ),
					new DateField_Disabled("Created", _t('AssetTableField.CREATED','First uploaded')),
					new DateField_Disabled("LastEdited", _t('AssetTableField.LASTEDIT','Last changed'))
				)
			)
		);
				
		if(is_a($childData,'Image')) {
			$big = $childData->URL;
			$thumbnail = $childData->getFormattedImage('AssetLibraryPreview')->URL;
			
			$detailFormFields->addFieldToTab("BottomRoot.Main", 
				new ReadonlyField("Dimensions", _t('AssetTableField.DIM','Dimensions')),
				"Created"
			);

			$detailFormFields->addFieldToTab("BottomRoot", 
				new Tab("Image",
					new LiteralField("ImageFull",
						'<a id="ImageEditorActivator" href="javascript: void(0)">' . "<img id='thumbnailImage' src='{$thumbnail}?r=" . rand(1,100000)  . "' alt='{$childData->Name}' /><p>Edit this image</p>" . '</a>' .
						'<script type="text/javascript" src="cms/javascript/ImageEditor/Activator.js"></script><script type="text/javascript">var imageActivator = new ImageEditor.Activator.initialize();Event.observe("ImageEditorActivator","click",imageActivator.onOpen);</script>'
					)
				),
				'Main'
			);
			
			if(class_exists('GalleryFile')) {
				$detailFormFields->addFieldToTab("BottomRoot", 
					new Tab("Gallery Options",
						new TextField( "Content", "Caption" )
					)
				);
			}
		}
		else if (class_exists('GalleryFile')) {
			if( $childData->Extension == 'swf' ) {
				$detailFormFields->addFieldToTab("BottomRoot", 
					new Tab("Gallery Options",
						new TextField( "Content", "Caption" ),
						new TextField( 'PopupWidth', 'Popup Width' ),
						new TextField( 'PopupHeight', 'Popup Height' ),
						new HeaderField( 'SWF File Options' ),
						new CheckboxField( 'Embed', 'Is A Flash Document' ),
						new CheckboxField( 'LimitDimensions', 'Limit The Dimensions In The Popup Window' )
					)
				);
			}
			else {
				$detailFormFields->addFieldToTab("BottomRoot", 
					new Tab("Gallery Options",
						new TextField( "Content", "Caption" ),
						new TextField( 'PopupWidth', 'Popup Width' ),
						new TextField( 'PopupHeight', 'Popup Height' )
					)
				);
			}
		}
						
		if($childData && $childData->hasMethod('BackLinkTracking')) {
			$links = $childData->BackLinkTracking();
			if($links->exists()) {
				foreach($links as $link) {
					$backlinks[] = "<li><a href=\"admin/show/$link->ID\">" . $link->Breadcrumbs(null,true). "</a></li>";
				}
				$backlinks = "<div style=\"clear:left\">". _t('AssetTableField.PAGESLINKING','The following pages link to this file:') ."<ul>" . implode("",$backlinks) . "</ul>";
			}
			if(!isset($backlinks)) $backlinks = "<p>". _t('AssetTableField.NOLINKS',"This file hasn't been linked to from any pages.") ."</p>";
			$detailFormFields->addFieldToTab("BottomRoot.Links", new LiteralField("Backlinks", $backlinks));
		}
		
		// the ID field confuses the Controller-logic in finding the right view for ReferencedField
		$detailFormFields->removeByName('ID');
		// add a namespaced ID instead thats "converted" by saveComplexTableField()
		$detailFormFields->push(new HiddenField("ctf[childID]","",$childID));
		$detailFormFields->push(new HiddenField("ctf[ClassName]","",$this->sourceClass));
			
		$readonly = ($this->methodName == "show");
		$form = new ComplexTableField_Popup($this, "DetailForm", $detailFormFields, $this->sourceClass, $readonly);
			
		if (is_numeric($childID)) {
			if ($methodName == "show" || $methodName == "edit") {
				$form->loadDataFrom($childData);
			}
		}
		
		if( !$folder->userCanEdit() || $methodName == "show") {
			$form->makeReadonly();
		}
		
		return $form;
	}
	
}
?>
