<?php
class CMSFileAddController extends LeftAndMain {

	private static $url_segment = 'assets/add';
	private static $url_priority = 60;
	private static $required_permission_codes = 'CMS_ACCESS_AssetAdmin';
	private static $menu_title = 'Files';
	private static $tree_class = 'Folder';

//	public function upload($request) {
//		$formHtml = $this->renderWith(array('AssetAdmin_UploadContent'));
//		if($request->isAjax()) {
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
		if($id && is_numeric($id) && $id > 0) {
			$folder = DataObject::get_by_id('Folder', $id);
			if($folder && $folder->exists()) {
				return $folder;
			}
		}
		return new Folder();
	}

	/**
	 * Return fake-ID "root" if no ID is found (needed to upload files into the root-folder)
	 */
	public function currentPageID() {
		$request = $this->getRequest();
		if (is_numeric($request->requestVar('ID')))	{
			return $request->requestVar('ID');
		} elseif (is_numeric($request->param('ID'))) {
			return $request->param('ID');
		} else {
			return 0;
		}
	}

	/**
	 * @param null $id Not used.
	 * @param null $fields Not used.
	 * @return Form
	 * @todo what template is used here? AssetAdmin_UploadContent.ss doesn't seem to be used anymore
	 */
	public function getEditForm($id = null, $fields = null) {
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/AssetUploadField.js');
		Requirements::css(FRAMEWORK_DIR . '/css/AssetUploadField.css');

		$folder = $this->currentPage();

		$uploadField = UploadField::create('AssetUploadField', '');
		$uploadField->setConfig('previewMaxWidth', 40);
		$uploadField->setConfig('previewMaxHeight', 30);
		$uploadField->setConfig('changeDetection', false);
		$uploadField->addExtraClass('ss-assetuploadfield');
		$uploadField->removeExtraClass('ss-uploadfield');
		$uploadField->setTemplate('AssetUploadField');

		if($folder->exists() && $folder->getFilename()) {
			// The Upload class expects a folder relative *within* assets/
			$path = preg_replace('/^' . preg_quote(ASSETS_DIR, '/') . '\//', '', $folder->getFilename());
			$uploadField->setFolderName($path);
		} else {
			$uploadField->setFolderName('/'); // root of the assets
		}

		$exts = $uploadField->getValidator()->getAllowedExtensions();
		asort($exts);
		$uploadField->Extensions = implode(', ', $exts);

		$form = CMSForm::create(
			$this,
			'EditForm',
			new FieldList(
				$uploadField,
				new HiddenField('ID')
			),
			new FieldList()
		)->setHTMLID('Form_EditForm');
		$form->setResponseNegotiator($this->getResponseNegotiator());
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

		$this->extend('updateEditForm', $form);

		return $form;
	}

	/**
	 * @param bool $unlinked
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);

		// The root element should explicitly point to the root node.
		$items[0]->Link = Controller::join_links(singleton('AssetAdmin')->Link('show'), 0);

		// Enforce linkage of hierarchy to AssetAdmin
		foreach($items as $item) {
			$baselink = $this->Link('show');
			if(strpos($item->Link, $baselink) !== false) {
				$item->Link = str_replace($baselink, singleton('AssetAdmin')->Link('show'), $item->Link);
			}
		}

		$items->push(new ArrayData(array(
			'Title' => _t('AssetAdmin.Upload', 'Upload'),
			'Link' => $this->Link()
		)));

		return $items;
	}

}
