<?php

class FilesystemSyncTask extends BuildTask {
	protected $title = "Sync Files & Images assets";
	
	protected $description = "The Files & Images system in SilverStripe maintains its own database
	 	of the contents of the assets/ folder.  This action will update that database, and
		should be called whenever files are added to the assets/ folder from outside
		SilverStripe, for example, if an author uploads files via FTP.";
	
	function run($request) {
		echo Filesystem::sync();
	}
}