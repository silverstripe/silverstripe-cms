<?php
/**
 * This class lets you export a static copy of your site.
 * It creates a huge number of folders each containing an index.html file.
 * This preserves the URL naming format.
 * 
 * Requirements: Unix Filesystem supporting symlinking. Doesn't work on Windows.
 * 
 * <b>Usage</b>
 * 
 * The exporter can only be invoked through a URL. Usage on commandline (through [sake](sake)) is not possible at the moment, as we're sending a file to the browser for download.
 * 
 * <pre>http://localhost/StaticExporter/export</pre>
 * 
 * Specify a custom baseurl in case you want to deploy the static HTML pages on a different host:
 * <pre>http://localhost/StaticExporter/export?baseurl=http://example.com</pre>
 * 
 * @see StaticPublisher
 * 
 * @package cms
 * @subpackage export
 */
class StaticExporter extends Controller {

	static $allowed_actions = array(
		'index', 
		'export', 
	);

	function init() {
		parent::init();
		
		$canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
		if(!$canAccess) return Security::permissionFailure($this);
	}
		
	
	function Link($action = null) {
		return "StaticExporter/$action";
	}
	
	function index() {
		echo "<h1>"._t('StaticExporter.NAME','Static exporter')."</h1>";
		echo $this->StaticExportForm()->forTemplate();
	}
	
	function StaticExportForm() {
		return new Form($this, 'StaticExportForm', new FieldList(
			// new TextField('folder', _t('StaticExporter.FOLDEREXPORT','Folder to export to')),
			new TextField('baseurl', _t('StaticExporter.BASEURL','Base URL'))
		), new FieldList(
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
			$subfolder   = "$tmpFolder/" . trim($page->RelativeLink(null, true), '/');
			$contentfile = "$tmpFolder/" . trim($page->RelativeLink(null, true), '/') . '/index.html';
			
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
		$response = SS_HTTPRequest::send_file($archiveContent, "$baseFolderName.tar.gz", 'application/x-tar-gz');
		echo $response->output();
	}
	
}

