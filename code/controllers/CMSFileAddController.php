<?php
class CMSFileAddController extends LeftAndMain {

	static $url_segment = 'assets/add';
	static $url_priority = 60;
	static $required_permission_codes = 'CMS_ACCESS_AssetAdmin';
	static $menu_title = 'Files';
	public static $tree_class = 'Folder';
	
//	public function upload($request) {
//		$formHtml = $this->renderWith(array('AssetAdmin_UploadContent'));
//		if($this->isAjax()) {
//			return $formHtml;
//		} else {
//			return $this->customise(array(
//				'Content' => $formHtml
//			))->renderWith(array('AssetAdmin', 'LeftAndMain'));
//		}
//	}

	/**
	 * Custom currentPage() method to handle opening the 'root' folder
	 */
	public function currentPage() {
		$id = $this->currentPageID();
		if($id && is_numeric($id)) {
			return DataObject::get_by_id('Folder', $id);
		} else {
			// ID is either '0' or 'root'
			return singleton('Folder');
		}
	}

	/**
	 * Return fake-ID "root" if no ID is found (needed to upload files into the root-folder)
	 */
	public function currentPageID() {
		if($this->request->requestVar('ID'))	{
			return $this->request->requestVar('ID');
		} elseif (is_numeric($this->urlParams['ID'])) {
			return $this->urlParams['ID'];
		} elseif(Session::get("{$this->class}.currentPage")) {
			return Session::get("{$this->class}.currentPage");
		} else {
			return "root";
		}
	}

	/**
	 * @return Form
	 * @todo what template is used here? AssetAdmin_UploadContent.ss doesn't seem to be used anymore
	 */
	public function getEditForm($id = null, $fields = null) {
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/AssetUploadField.js');
		Requirements::css(SAPPHIRE_DIR . '/css/AssetUploadField.css');

		$folder = $this->currentPage();

		$uploadField = Object::create('UploadField', 'AssetUploadField', '');
		$uploadField->setConfig('previewMaxWidth', 40);
		$uploadField->setConfig('previewMaxHeight', 30);
		$uploadField->addExtraClass('ss-assetuploadfield');
		$uploadField->removeExtraClass('ss-uploadfield');
		$uploadField->setTemplate('AssetUploadField');
		if ($folder->exists() && $folder->getFilename()) {
			// The Upload class expects a folder relative *within* assets/
			$path = preg_replace('/^' . ASSETS_DIR . '\//', '', $folder->getFilename());
			$uploadField->setFolderName($path);
		}

		$form = new Form(
			$this, 
			'getEditForm', 
			new FieldList($uploadField, new HiddenField('ID')), 
			new FieldList()
		);
		$form->addExtraClass('center cms-edit-form ' . $this->BaseCSSClasses());
		// Don't use AssetAdmin_EditForm, as it assumes a different panel structure
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$form->Fields()->push(
			new LiteralField(
				'BackLink',
				sprintf(
					'<a href="%s" class="backlink ss-ui-button cms-panel-link" data-icon="back">%s</a>',
					Controller::join_links(singleton('AssetAdmin')->Link('show'), $folder ? $folder->ID : 0),
					_t('AssetAdmin.BackToFolder', 'Back to folder')
				)
			)
		);
		$form->loadDataFrom($folder);

		return $form;
	}

	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);

		// The root element should explicitly point to the root node.
		// Used in CMSFileAddController subclass as well, so specifically link to AssetAdmin
		$items[0]->Link = Controller::join_links(singleton('AssetAdmin')->Link('show'), 'root');

		$items->push(new ArrayData(array(
			'Title' => _t('AssetAdmin.Upload', 'Upload'),
			'Link' => $this->Link()
		)));
		
		return $items;
	}

}