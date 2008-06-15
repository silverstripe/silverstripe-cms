<?php
/**
 * A special kind of form used to make the action dialogs that appear just underneath the top-right
 * buttons in the CMS
 * 
 * @package cms
 * @subpackage core
 */
class CMSActionOptionsForm extends Form {
	function FormAttributes() {
		return "class=\"actionparams\" style=\"display:none\" " . parent::FormAttributes();
	}
	function FormName() {
		$action = $this->actions->First()->Name();
		return "{$action}_options";
	}
}

?>