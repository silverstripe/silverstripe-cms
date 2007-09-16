<?php

/**
 * This class lets you export a static copy of your site.
 * It creates a huge number of folders each containing an index.html file.
 * This preserves the URL naming format.
 */
class StaticExporter extends Controller {
	function init() {
		parent::init();
		if(!$this->can('AdminCMS')) {
			$messageSet = array(
				'default' => _t('LeftAndMain.PERMDEFAULT'),
				'alreadyLoggedIn' => _t('LeftAndMain.PERMALREADY'),
				'logInAgain' => _t('LeftAndMain.PERMAGAIN'),
			);

			Security::permissionFailure($this, $messageSet);
		}
	}


	function Link($action = null) {
		return "StaticExporter/$action";
	}

	function index() {
		echo "<h1>"._t('StaticExporter.NAME','Static exporter')."</h1>";
		echo $this->StaticExportForm()->forTemplate();
	}

	function StaticExportForm() {
		return new Form($this, 'StaticExportForm', new FieldSet(
			new TextField('folder', _t('StaticExporter.FOLDEREXPORT','Folder to export to')),
			new TextField('baseurl', _t('StaticExporter.BASEURL','Base URL'))
		), new FieldSet(
			new FormAction('export', _t('StaticExporter.EXPORTTOTHAT','Export to that folder'))
		));
	}

	function export() {

		if($_REQUEST['baseurl']) {
			$base = $_REQUEST['baseurl'];
			if(substr($base,-1) != '/') $base .= '/';
			Director::setBaseURL($base);
		}

		$folder = $_REQUEST['folder'];
		if($folder && file_exists($folder)) {
			$pages = DataObject::get("SiteTree");
			foreach($pages as $page) {
				$subfolder = "$folder/$page->URLSegment";
				$contentfile = "$folder/$page->URLSegment/index.html";

				echo "<li>$page->URLSegment/index.html";

				// Make the folder
				if(!file_exists($subfolder)) {
					mkdir($subfolder);
					chmod($subfolder,02775);
				}

				// Run the page
				Requirements::clear();
				$controllerClass = "{$page->class}_Controller";
				if(class_exists($controllerClass)) {
					$controller = new $controllerClass($page);

					$response = $controller->run( array() );
					$pageContent = $response->getBody();
					
					// Write to file
					if($fh = fopen($contentfile, 'w')) {
						fwrite($fh, $pageContent);
						fclose($fh);
					}
				}
			}
		} else {
			echo _t('StaticExporter.ONETHATEXISTS',"Please specify a folder that exists");
		}

	}

}

?>