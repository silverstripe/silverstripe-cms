<?php
/**
 * This class lets you export a static copy of your site.
 * It creates a huge number of folders each containing an index.html file.
 * This preserves the URL naming format.
 * @package cms
 * @subpackage export
 */
class StaticExporter extends Controller {
	function init() {
		parent::init();
		if(!Permission::check('ADMIN')) {
			$messageSet = array(
				'default' => _t('LeftAndMain.PERMDEFAULT', 'Enter your email address and password to access the CMS.'),
				'alreadyLoggedIn' => _t('LeftAndMain.PERMALREADY', 'I\'m sorry, but you can\'t access that part of the CMS.  If you want to log in as someone else, do so below'),
				'logInAgain' => _t('LeftAndMain.PERMAGAIN', 'You have been logged out of the CMS.  If you would like to log in again, enter a username and password below.'),
			);

			Security::permissionFailure($this, $messageSet);
			return;
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
			// new TextField('folder', _t('StaticExporter.FOLDEREXPORT','Folder to export to')),
			new TextField('baseurl', _t('StaticExporter.BASEURL','Base URL'))
		), new FieldSet(
			new FormAction('export', _t('StaticExporter.EXPORTTO','Export to that folder'))
		));
	}
	
	function export() {
		
		if($_REQUEST['baseurl']) {
			$base = $_REQUEST['baseurl'];
			if(substr($base,-1) != '/') $base .= '/';
			Director::setBaseURL($base);
		}
		
		$folder = '/tmp/static-export/' . project();
		if(!project()) $folder .= 'site';
		if(!file_exists($folder)) mkdir($folder, Filesystem::$folder_create_mask, true);

		$f1 = ASSETS_PATH;
		$f2 = Director::baseFolder() . '/' . project();
		`cd $folder; ln -s $f1; ln -s $f2`;

		
		$baseFolder = basename($folder);
		
		if($folder && file_exists($folder)) {
			$pages = DataObject::get("SiteTree");
			foreach($pages as $page) {
				$subfolder = "$folder/$page->URLSegment";
				$contentfile = "$folder/$page->URLSegment/index.html";
				
				// Make the folder				
				if(!file_exists($subfolder)) {
					mkdir($subfolder, Filesystem::$folder_create_mask);
				}
				
				// Run the page
				Requirements::clear();
				$controllerClass = "{$page->class}_Controller";
				if(class_exists($controllerClass)) {
					$controller = new $controllerClass($page);
					$pageContent = $controller->handleRequest(new HTTPRequest('GET',''))->getBody();
					
					// Write to file
					if($fh = fopen($contentfile, 'w')) {
						fwrite($fh, $pageContent->getBody());
						fclose($fh);
					}
				}
			}
			
			copy("$folder/home/index.html", "$folder/index.html");			

			`cd /tmp/static-export; tar -czhf $baseFolder.tar.gz $baseFolder`;

			$content = file_get_contents("/tmp/static-export/$baseFolder.tar.gz");
			Filesystem::removeFolder('/tmp/static-export');

			HTTP::sendFileToBrowser($content, "$baseFolder.tar.gz");
			return null;
			
		} else {
			echo _t('StaticExporter.ONETHATEXISTS',"Please specify a folder that exists");
		}
			
	}
	
}

?>