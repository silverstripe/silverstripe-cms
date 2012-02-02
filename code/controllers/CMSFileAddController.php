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
	public function getEditForm() {
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/AssetUploadField.js');
		Requirements::css(SAPPHIRE_DIR . '/css/AssetUploadField.css');

		$uploadField = Object::create('UploadField', 'AssetUploadField', '');
		$uploadField->setConfig('previewMaxWidth', 40);
		$uploadField->setConfig('previewMaxHeight', 30);
		$uploadField->addExtraClass('ss-assetuploadfield');
		$uploadField->removeExtraClass('ss-uploadfield');
		$uploadField->setTemplate('AssetUploadField');
		if ($this->currentPage()->exists() && $this->currentPage()->getFilename()) {
			$uploadField->setFolderName($this->currentPage()->getFilename());
		}

		$form = new Form($this, 'getEditForm', new FieldList($uploadField), new FieldList());
		$form->addExtraClass('cms-content center cms-edit-form ' . $this->BaseCSSClasses());
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

		return $form;
	}


}