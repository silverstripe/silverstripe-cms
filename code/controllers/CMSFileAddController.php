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
		$UploadField = Object::create('UploadField', 'AssetUploadField', '')->performAssetUploadFieldTransformation();
		if ($this->currentPage()->exists() && $this->currentPage()->getFilename())
			$UploadField->setFolderName($this->currentPage()->getFilename());
		$form = new Form($this, 'getEditForm', new FieldList($UploadField), new FieldList());
		$form->addExtraClass('cms-content center cms-edit-form ' . $this->BaseCSSClasses());
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		return $form;
	}


}