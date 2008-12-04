<?php
/**
 * This class lets you export a static copy of your site.
 * It creates a huge number of folders each containing an index.html file.
 * This preserves the URL naming format.
 * 
 * Requirements: Unix Filesystem supporting symlinking.
 * Doesn't work on Windows.
 * 
 * @see StaticPublisher
 * 
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
		// specify custom baseurl for publishing to other webroot
		if(isset($_REQUEST['baseurl'])) {
			$base = $_REQUEST['baseurl'];
			if(substr($base,-1) != '/') $base .= '/';
			Director::setBaseURL($base);
		}
		
		// setup temporary folders
		$tmpBaseFolder = TEMP_FOLDER . '/static-export';
		$tmpFolder = (project()) ? "$tmpBaseFolder/" . project() : "$tmpBaseFolder/site";
		if(!file_exists($tmpFolder)) Filesystem::makeFolder($tmpFolder);
		$baseFolderName = basename($tmpFolder);

		// symlink /assets
		$f1 = ASSETS_PATH;
		$f2 = Director::baseFolder() . '/' . project();
		`cd $tmpFolder; ln -s $f1; ln -s $f2`;

		// iterate through all instances of SiteTree
		$pages = DataObject::get("SiteTree");
		foreach($pages as $page) {
			$subfolder = "$tmpFolder/$page->URLSegment";
			$contentfile = "$tmpFolder/$page->URLSegment/index.html";
			
			// Make the folder				
			if(!file_exists($subfolder)) {
				Filesystem::makeFolder($subfolder);
			}
			
			// Run the page
			Requirements::clear();
			$link = Director::makeRelative($page->Link());
			$response = Director::test($link);

			// Write to file
			if($fh = fopen($contentfile, 'w')) {
				fwrite($fh, $response->getBody());
				fclose($fh);
			}
		}

		// copy homepage (URLSegment: "home") to webroot
		copy("$tmpFolder/home/index.html", "$tmpFolder/index.html");			
		
		// archive all generated files
		`cd $tmpBaseFolder; tar -czhf $baseFolderName.tar.gz $baseFolderName`;
		$archiveContent = file_get_contents("$tmpBaseFolder/$baseFolderName.tar.gz");
		
		// remove temporary files and folder
		Filesystem::removeFolder($tmpBaseFolder);
		
		// return as download to the client
		$response = HTTPRequest::send_file($archiveContent, "$baseFolderName.tar.gz", 'application/x-tar-gz');
		echo $response->output();
	}
	
}

?>