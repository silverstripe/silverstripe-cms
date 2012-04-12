<?php

/**
 * This static publisher can be used to deploy static content to multiple hosts, by generating the cache files locally and then rsyncing then to
 * each destination box.  This can be used to set up a load-balanced collection of static servers.
 * 
 * @see http://doc.silverstripe.com/doku.php?id=staticpublisher
 *
 * @package cms
 * @subpackage publishers
 */
class RsyncMultiHostPublisher extends FilesystemPublisher {
	/**
	 * Array of rsync targets to publish to.  These can either be local file names, or scp-style targets, in the form "user@server:path"
	 */
	protected static $targets = array();
	
	protected static $excluded_folders = array();
	
	/**
	 * Set the targets to publish to.
	 * If target is an scp-style remote path, no password is accepted - we assume key-based authentication to be set up on the application server
	 * initiating the publication.
	 * 
	 * @param $targets An array of targets to publish to.  These can either be local file names, or scp-style targets, in the form "user@server:path"
	 */
	static function set_targets($targets) {
		self::$targets = $targets;
	}
	
	/**
	 * Specify folders to exclude from the rsync
	 * For example, you could exclude assets.
	 */
	static function set_excluded_folders($folders) {
		self::$excluded_folders = $folders;
	}

	function publishPages($urls) {
		parent::publishPages($urls);
		$base = Director::baseFolder();
		$framework = FRAMEWORK_DIR;

		// Get variable that can turn off the rsync component of publication 
		if(isset($_GET['norsync']) && $_GET['norsync']) return;
		
		$extraArg = "";
		if(self::$excluded_folders) foreach(self::$excluded_folders as $folder) {
			$extraArg .= " --exclude " . escapeshellarg($folder);
		}
		
		foreach(self::$targets as $target) {
			// Transfer non-PHP content from everything to the target; that will ensure that we have all the JS/CSS/etc
			$rsyncOutput = `cd $base; rsync -av -e ssh --exclude /.htaccess --exclude /web.config --exclude '*.php' --exclude '*.svn' --exclude '*.git' --exclude '*~' $extraArg --delete . $target`;
			// Then transfer "safe" PHP from the cache/ directory
			$rsyncOutput .= `cd $base; rsync -av -e ssh --exclude '*.svn' --exclude '*~' $extraArg --delete cache $target`;
			// Transfer framework/static-main.php to the target
			$rsyncOutput .= `cd $base; rsync -av -e ssh --delete $framework/static-main.php $target/$framework`;
			if(StaticPublisher::echo_progress()) echo $rsyncOutput;
		}
	}
	
}
