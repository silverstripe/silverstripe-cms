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
	 * @config
	 * Array of rsync targets to publish to.  These can either be local file names, or scp-style targets, in the form "user@server:path"
	 */
	private static $targets = array();
	
	/**
	 * @config
	 * @var array
	 */
	private static $excluded_folders = array();
	
	/**
	 * Set the targets to publish to.
	 * If target is an scp-style remote path, no password is accepted - we assume key-based authentication to be set up on the application server
	 * initiating the publication.
	 *
	 * @deprecated  3.2 Use the "RsyncMultiHostPublisher.targets" config setting instead
	 * @param $targets An array of targets to publish to.  These can either be local file names, or scp-style targets, in the form "user@server:path"
	 */
	static public function set_targets($targets) {
		Deprecation::notice('3.2', 'Use the "RsyncMultiHostPublisher.targets" config setting instead');
		Config::inst()->update('RsyncMultiHostPublisher', 'targets', $targets);
	}
	
	/**
	 * Specify folders to exclude from the rsync
	 * For example, you could exclude assets.
	 *
	 * @deprecated  3.2 Use the "RsyncMultiHostPublisher.excluded_folders" config setting instead
	 */
	static public function set_excluded_folders($folders) {
		Deprecation::notice('3.2', 'Use the "RsyncMultiHostPublisher.excluded_folders" config setting instead');
		Config::inst()->update('RsyncMultiHostPublisher', 'excluded_folders', $folders);
	}

	public function publishPages($urls) {
		parent::publishPages($urls);
		$base = Director::baseFolder();
		$framework = FRAMEWORK_DIR;

		// Get variable that can turn off the rsync component of publication 
		if(isset($_GET['norsync']) && $_GET['norsync']) return;
		
		$extraArg = "";
		if($this->config()->excluded_folders) foreach($this->config()->excluded_folders as $folder) {
			$extraArg .= " --exclude " . escapeshellarg($folder);
		}
		
		foreach((array)$this->config()->targets as $target) {
			// Transfer non-PHP content from everything to the target; that will ensure that we have all the JS/CSS/etc
			$rsyncOutput = `cd $base; rsync -av -e ssh --exclude /.htaccess --exclude /web.config --exclude '*.php' --exclude '*.svn' --exclude '*.git' --exclude '*~' $extraArg --delete . $target`;
			// Then transfer "safe" PHP from the cache/ directory
			$rsyncOutput .= `cd $base; rsync -av -e ssh --exclude '*.svn' --exclude '*~' $extraArg --delete cache $target`;
			// Transfer framework/static-main.php to the target
			$rsyncOutput .= `cd $base; rsync -av -e ssh --delete $framework/static-main.php $target/$framework`;
			if(Config::inst()->get('StaticPublisher', 'echo_progress')) echo $rsyncOutput;
		}
	}
	
}
