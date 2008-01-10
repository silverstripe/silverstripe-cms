<?php

/**
 * @package cms
 * @subpackage newsletter
 */

/**
 * Subclass of DropdownField for showing a list of the newsletter templates available.
 * @package cms
 * @subpackage newsletter
 */
class TemplateList extends DropdownField {
	
	protected $templatePath;
	
	function __construct( $name, $title, $value, $path, $form = null ) {
		$this->templatePath = $path;
		parent::__construct( $name, $title, $this->getTemplates(), $value, $form );
	}
	
	private function getTemplates() {
	  $templates = array( "" => _t('TemplateList.NONE', 'None') );

		$absPath = Director::baseFolder();
		if( $absPath{strlen($absPath)-1} != "/" )
			$absPath .= "/";
		
		$absPath .= $this->templatePath;
		if(is_dir($absPath)) {
			$templateDir = opendir( $absPath );
			
			// read all files in the directory
			while( ( $templateFile = readdir( $templateDir ) ) !== false ) {
				// *.ss files are templates
				if( preg_match( '/(.*)\.ss$/', $templateFile, $match ) )
					$templates[$match[1]] = $match[1];
			}
		}
		
		return $templates;
	}
	
	function setController( $controller ) {
		$this->controller = $controller;
	}
}
?>