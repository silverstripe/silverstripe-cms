<?php
class CMSFileAddController extends AssetAdmin {

	static $url_segment = 'assets/add';

	static $url_priority = 60;
	
	function getEditForm() {
		$form = new Form(
			$this,
			'Form',
			new FieldList(
				// TODO Replace with UploadField
				new LiteralField("UploadIframe",$this->getUploadIframe())
			),
			new FieldList(
			)
		);
		$form->addExtraClass('cms-content center cms-edit-form ' . $this->BaseCSSClasses());
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		return $form;
	}
	

	/**
	 * Display the upload form.  Returns an iframe tag that will show admin/assets/uploadiframe.
	 */
	function getUploadIframe() {
		return <<<HTML
		<iframe name="AssetAdmin_upload" src="admin/assets/uploadiframe/{$this->ID}" id="AssetAdmin_upload" border="0" style="border-style none !important; width: 97%; min-height: 300px; height: 100%; height: expression(document.body.clientHeight) !important;">
		</iframe>
HTML;
	}

}