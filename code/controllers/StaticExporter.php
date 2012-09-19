<?php
/**
 * This class lets you export a static copy of your site either as an tar 
 * archive through the web browser or through the command line.
 *
 * The exporter will preserve the URL naming format of your pages by 
 * creating a number of subfolders folders each containing an index.html 
 * file.
 *
 * <b>Requirements:</b>
 *	- Unix Filesystem supporting symlinking. 
 *	- Tar installed (for generating an archive)
 *	- Doesn't work on Windows.
 * 
 * <b>Usage</b>
 * 
 * The exporter can be invoked through a URL or on the commandline (through 
 * [sake](sake)).
 *
 * To download a tar.gz through the browser visit the following URL while
 * logged in as an administrator:
 *
 * <code>
 * http://localhost/StaticExporter/
 * </code>
 *
 * Or you can generate the export via sake:
 *
 * <code>
 * sake dev/tasks/StaticExporter_Task "baseurl=http://example.com"
 * </code>
 *
 * <b>Options</b>
 *
 * Specify a custom baseurl in case you want to deploy the static HTML pages on 
 * a different host:
 *
 * <code>http://localhost/StaticExporter/export?baseurl=http://example.com</pre>
 * 
 * @see StaticPublisher
 * 
 * @package cms
 * @subpackage export
 */
class StaticExporter extends Controller {

	public static $allowed_actions = array(
		'index', 
		'export', 
		'StaticExportForm'
	);

	public function init() {
		parent::init();
		
		$canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
		if(!$canAccess) return Security::permissionFailure($this);
	}
		
	
	public function Link($action = null) {
		return "StaticExporter/$action";
	}
	
	public function index() {
		return array(
			'Title' => _t('StaticExporter.NAME','Static exporter'),
			'Form' => $this->StaticExportForm()->forTemplate()
		);
	}
	
	public function StaticExportForm() {
		$form = new Form($this, 'StaticExportForm', new FieldList(
			new TextField('baseurl', _t('StaticExporter.BASEURL','Base URL'))
		), new FieldList(
			new FormAction('export', _t('StaticExporter.EXPORT','Export'))
		));

		return $form;
	}


	public function export() {
		if(isset($_REQUEST['baseurl'])) {
			$base = $_REQUEST['baseurl'];
			if(substr($base,-1) != '/') $base .= '/';
		}
		else {
			$base = Director::baseURL();
		}

		$folder = TEMP_FOLDER . '/static-export';
		$project = project();

		$exported = $this->doExport($base, $folder .'/'. $project, false);

		`cd $folder; tar -czhf $project-export.tar.gz $project`;

		$archiveContent = file_get_contents("$folder/$project-export.tar.gz");
		
		
		// return as download to the client
		$response = SS_HTTPRequest::send_file(
			$archiveContent, 
			"$project-export.tar.gz", 
			'application/x-tar-gz'
		);
		
		echo $response->output();
	}

	/**
	 * Exports the website with the given base url. Returns the path where the
	 * exported version of the website is located.
	 *
	 * @param string website base url
	 * @param string folder to export the site into
	 * @param bool symlink assets
	 * @param bool suppress output progress
	 *
	 * @return string path to export
	 */
	public function doExport($base, $folder, $symlink = true, $quiet = true) {
		ini_set('max_execution_time', 0);
		Director::setBaseURL($base);

		if(is_dir($folder)) {
			Filesystem::removeFolder($folder);
		}

		Filesystem::makeFolder($folder);
		
		// symlink or copy /assets
		$f1 = ASSETS_PATH;
		$f2 = Director::baseFolder() . '/' . project();

		if($symlink) {
			`cd $folder; ln -s $f1; ln -s $f2`;
		}
		else {
			`cp -R $f1 $folder; cp -R $f2 $folder`;
		}

		// iterate through items we need to export
		$objs = $this->getObjectsToExport();

		if($objs) {
			$total = $objs->count();
			$i = 1;

			foreach($objs as $obj) {
				$link = $obj->RelativeLink(null, true);

				$subfolder   = "$folder/" . trim($link, '/');
				$contentfile = "$folder/" . trim($link, '/') . '/index.html';
				
				// Make the folder				
				if(!file_exists($subfolder)) {
					Filesystem::makeFolder($subfolder);
				}
				
				// Run the page
				Requirements::clear();
				$link = Director::makeRelative($obj->Link());
				$response = Director::test($link);

				// Write to file
				if($fh = fopen($contentfile, 'w')) {
					if(!$quiet) printf("-- (%s/%s) Outputting page (%s)%s", $i, $total, $obj->RelativeLink(null, true), PHP_EOL);

					fwrite($fh, $response->getBody());
					fclose($fh);
				}

				$i++;
			}
		}

		return $folder;
	}

	/**
	 * Return a list of publishable instances for the exporter to include. The
	 * only requirement is that for this list of objects, each one implements
	 * the RelativeLink() and Link() method.
	 *
	 * @return SS_List
	 */
	public function getObjectsToExport() {
		$objs = SiteTree::get();
		$this->extend('alterObjectsToExport', $objs);

		return $objs;
	}
}

/**
 * @package cms
 * @subpackage export
 */
class StaticExporter_Task extends BuildTask {

	public function run($request) {
		$export = new StaticExporter();

		$url = $request->getVar('baseurl');
		$sym = $request->getVar('symlink');
		$quiet = $request->getVar('quiet');
		$folder = $request->getVar('path');

		if(!$folder) $folder = TEMP_FOLDER . '/static-export';

		$url = ($url) ? $url : Director::baseURL(); 
		$symlink = ($sym != "false");
		$quiet = ($quiet) ? $quiet : false;

		if(!$quiet) printf("Exporting website with %s base URL... %s", $url, PHP_EOL);
		$path = $export->doExport($url, $folder, $symlink, $quiet);

		if(!$quiet) printf("Completed. Website exported to %s. %s", $path, PHP_EOL);
	}
}