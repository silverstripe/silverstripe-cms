<?php

/**
 * This static publisher can be used to deploy static content to multiple hosts, by generating the cache files locally and then rsyncing then to
 * each destination box.  This can be used to set up a load-balanced collection of static servers.
 */
class RsyncMultiHostPublisher extends FilesystemPublisher {
	/**
	 * Array of rsync targets to publish to.  These can either be local file names, or scp-style targets, in the form "user@server:path"
	 */
	static $targets;
	
	/**
	 * Set the targets to publish to.
	 * @param $targets An array of targets to publish to.  These can either be local file names, or scp-style targets, in the form "user@server:path"
	 */
	static function set_targets($targets) {
		self::$targets = $targets;
	}

	function publishPages($urls) {
		parent::publishPages($urls);
		$base = Director::baseFolder();

		// Get variable that can turn off the rsync component of publication 
		if(isset($_GET['norsync']) && $_GET['norsync']) return;
		
		foreach(self::$targets as $target) {
			// Transfer non-PHP content from everything to the target; that will ensure that we have all the JS/CSS/etc
			$rsyncOutput = `cd $base; rsync -av -e ssh --exclude /.htaccess --exclude '*.php' --exclude '*.svn' --exclude '*~' --delete . $target`;
			// Then transfer "safe" PHP from the cache/ directory
			$rsyncOutput .= `cd $base; rsync -av -e ssh --exclude '*.svn' --exclude '*~' --delete cache $target`;
			if(StaticPublisher::echo_progress()) echo $rsyncOutput;
		}
	}
	
}
