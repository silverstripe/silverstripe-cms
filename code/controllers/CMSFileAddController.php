<?php
class CMSFileAddController extends AssetAdmin {

	static $url_segment = 'assets/add';

	static $url_priority = 60;
	
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

	function Tools() {
		return false;
	}

}