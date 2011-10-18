<?php

include_once dirname(__FILE__) . '/SilverStripeBuildTask.php';

/**
 * A phing task to load modules from a specific URL via SVN, git checkout, or
 * through the "piston" binary (http://piston.rubyforge.org).
 *
 * Passes commands directly to the commandline to actually perform the
 * svn checkout/updates, so you must have these on your path when this
 * runs.
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 *
 */
class LoadModulesTask extends SilverStripeBuildTask {
	/**
	 * Character used to separate the module/revision name from the output path
	 */
	const MODULE_SEPARATOR = ':';

	/**
	 * The file that defines the dependency
	 *
	 * @var String
	 */
	private $file = '';
	/**
	 * Optionally specify a module name
	 *
	 * @var String
	 */
	private $name = '';
	/**
	 * And a module url
	 * @var String
	 */
	private $url = '';
	
	/**
	 * Is this a non-interactive build session?
	 * @var boolean
	 */
	private $nonInteractive = false;
	
	public function setNoninteractive($v) {
		if (!strpos($v, '${') && $v == 'true' || $v == 1) {
			$this->nonInteractive = true;
		}
	}

	public function setFile($v) {
		$this->file = $v;
	}

	public function setName($v) {
		$this->name = $v;
	}

	public function setUrl($v) {
		$this->url = $v;
	}

	public function main() {
		// $this->configureEnvFile();

		if ($this->name) {
			$this->loadModule($this->name, $this->url);
		} else {
			// load the items from the dependencies file
			if (!file_exists($this->file)) {
				throw new BuildException("Modules file " . $this->modulesFile . " cannot be read");
			}

			$items = file($this->file);
			foreach ($items as $item) {
				$item = trim($item);
				if (strpos($item, '#') === 0) {
					continue;
				}

				$bits = preg_split('/\s+/', $item);
				// skip malformed lines
				if (count($bits) < 2) {
					continue;
				}

				$moduleName = trim($bits[0], '/');
				$url = trim($bits[1], '/');
				$storeLocally = false;
				$usePiston = false;
				if (isset($bits[2])) {
					$devBuild = $bits[2] == 'true';
					$storeLocally = $bits[2] == 'local';
					$usePiston = $bits[2] == 'piston';
					if (isset($bits[3])) {
						$storeLocally = $bits[3] == 'local';
						$usePiston = $bits[3] == 'piston';
					}
				}
				
				$this->loadModule($moduleName, $url, $devBuild, $storeLocally, $usePiston);
			}
		}
	}

	/**
	 * Actually load the module!
	 *
	 * @param String $moduleName
	 * @param String $url
	 * @param boolean $devBuild
	 * 			Do we run a dev/build?
	 * @param boolean $storeLocally
	 *			Should we store the module locally, for it to be included in 
	 *			the local project's repository?
	 * @param boolean $usePiston Same as $storeLocally, but retain versioning metadata in piston.
	 */
	protected function loadModule($moduleName, $url, $devBuild = false, $storeLocally=false, $usePiston=false) {
		$git = strrpos($url, '.git') == (strlen($url) - 4);
		$branch = 'master';
		$cmd = '';
		
		$originalName = $moduleName;

		if (strpos($moduleName, self::MODULE_SEPARATOR) > 0) {
			$branch = substr($moduleName, strpos($moduleName, self::MODULE_SEPARATOR) + 1);
			$moduleName = substr($moduleName, 0, strpos($moduleName, self::MODULE_SEPARATOR));
		}

		$md = $this->loadMetadata();
		if (!isset($md['store'])) {
			// backwards compatibility
			$md['store'] = false;
		}
		
		// create loader
		if($usePiston) {
			$loader = new LoadModulesTask_PistonLoader($this, $moduleName, $url, $branch);
		} elseif($git) {
			$loader = new LoadModulesTask_GitLoader($this, $moduleName, $url, $branch);
		} else {
			$loader = new LoadModulesTask_SubversionLoader($this, $moduleName, $url, $branch);
		}

		// check the module out if it doesn't exist
		if (!file_exists($moduleName)) {
			$this->log("Check out $moduleName from $url");
			
			// Create new working copy
			$loader->checkout($storeLocally);

			// Ignore locally added modules from base working copy.
			// Only applies when this base contains versioning information.
			// Note: This is specific to the base working copy, not the module itself.
			if (!$storeLocally && !$usePiston && file_exists('.gitignore')) {
				$gitIgnore = file_get_contents('.gitignore');
				if (strpos($gitIgnore, $moduleName) === false) {
					$this->exec("echo $$moduleName >> .gitignore");
				}
			}
		} else {
			$this->log("Updating $moduleName $branch from $url");
			
			// Check for modifications
			// TODO Shows all files as modified when switching repository types or branches'
			$overwrite = true;
			$mods = $loader->getModifiedFiles();
			if (strlen($mods) && !$storeLocally) {
				$this->log("The following files are locally modified");
				$this->log($mods);
				if (!$this->nonInteractive) {
					$overwrite = strtolower(trim($this->getInput("Overwrite local changes? [y/N]")));
					$overwrite = $overwrite == 'y';
				} 
			}

			// get the metadata and make sure it's not the same
			// TODO Doesn't handle switch from git to svn repositories
			if ($md && isset($md[$moduleName]) && isset($md[$moduleName]['url'])) {
				if (
					$md[$moduleName]['url'] != $url 
					|| $md[$moduleName]['store'] != $storeLocally
					|| $md[$moduleName]['piston'] != $usePiston
				) {
					if ($overwrite) {
					// delete the directory and reload the module
						$this->log("Deleting $moduleName and reloading");
						unset($md[$moduleName]);
						$this->writeMetadata($md);
						rrmdir($moduleName, true);
						// TODO Doesn't handle changes between svn/git/piston
						$loader->checkout($storeLocally);
						return;
					} else {
						throw new Exception("You have chosen not to overwrite changes, but also want to change your " .
							"SCM settings. Please resolve changes and try again");
					}
				}
			}
			
			// Update existing versioned copy
			$loader->update($overwrite);
		}

		// Write new metadata
		$metadata = array(
			'url' => $url,
			'store' => $storeLocally,
			'piston' => $usePiston,
			'branch' => str_replace($moduleName, '', $originalName),
		);
		$md[$moduleName] = $metadata;
		$this->writeMetadata($md);
		
		// Make sure to remove from the .gitignore file - don't need to do it EVERY 
		// run, but it's better than munging code up above
		if ($storeLocally && file_exists('.gitignore')) {
			$gitIgnore = file('.gitignore');
			$newIgnore = array();
			foreach ($gitIgnore as $line) {
				$line = trim($line);
				if (!$line || $line == $moduleName || $line == "$moduleName/") {
					continue;
				}
				$newIgnore[] = $line;
			}
			file_put_contents('.gitignore', implode("\n", $newIgnore));
		}
		
		if ($devBuild) $this->devBuild();
	}
	
	protected function loadMetadata() {
		$metadataFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phing-metadata';
		
		$md = array();
		if (file_exists($metadataFile)) {
			$md = unserialize(file_get_contents($metadataFile));
		}
		
		return $md;
	}
	
	protected function writeMetadata($md) {
		$metadataFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phing-metadata';
		file_put_contents($metadataFile, serialize($md));
	}


}

if (!function_exists('rrmdir')) {
	function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir")
						rrmdir($dir . "/" . $object); else
						unlink($dir . "/" . $object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}
}

class LoadModulesTask_Loader {
	
	/**
	 * @var SilverStripeBuildTask
	 */
	protected $callingTask;
	
	/**
	 * @var string
	 */
	protected $url;
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var string
	 */
	protected $branch;
	
	/**
	 * @var boolean
	 */
	protected $nonInteractive = false;
	
	/**
	 * @param SilverStripeBuildTask Phing crashes when extending the loader from SilverStripeBuildTask
	 * @param String
	 * @param String
	 * @param String
	 */
	function __construct($callingTask, $name, $url, $branch = null) {
		$this->callingTask = $callingTask;
		$this->name = $name;
		$this->url = $url;
		$this->branch = $branch;
	}
	
	/**
	 * Check out a new working copy.
	 * Call {@link storeLocally()} afterwards to remove versioning information
	 * from the working copy.
	 */
	function checkout($storeLocally = false) {
		// noop
	}
	
	/**
	 * Update an existing working copy
	 */
	function update($overwrite = true) {
		// noop
	}
	
	/**
	 * @return array
	 */
	function getModifiedFiles() {
		// noop
	}
	
}

class LoadModulesTask_GitLoader extends LoadModulesTask_Loader {
	
	function checkout($storeLocally = false) {
		$branch = $this->branch;
		$currentDir = getcwd();
		$this->callingTask->exec("git clone $this->url $this->name");
		
		if ($branch != 'master') {
			// check if we're also hooking onto a revision
			$commitId = null;
			if (strpos($this->branch, LoadModulesTask::MODULE_SEPARATOR) > 0) {
				$commitId = substr($branch, strpos($branch, LoadModulesTask::MODULE_SEPARATOR) + 1);
				$branch = substr($branch, 0, strpos($branch, LoadModulesTask::MODULE_SEPARATOR));
			}
			// need to make sure we've pulled from the correct branch also
			if ($branch != 'master') {
				$this->callingTask->exec("cd $this->name && git checkout -f -b $branch --track origin/$branch && cd \"$currentDir\"");
			}

			if ($commitId) {
				$this->callingTask->exec("cd $this->name && git checkout $commitId && cd \"$currentDir\"");
			}
		}
		
		if($storeLocally) rrmdir("$this->name/.git");
	}
	
	function getModifiedFiles() {
		$currentDir = getcwd();
		$statCmd = "git diff --name-status";
		return trim($this->callingTask->exec("cd $this->name && $statCmd && cd \"$currentDir\"", true));
	}
	
	function update($overwrite = true) {
		$branch = $this->branch;
		$currentDir = getcwd();

		$commitId = null;
		if (strpos($branch, LoadModulesTask::MODULE_SEPARATOR) > 0) {
			$commitId = substr($branch, strpos($branch, LoadModulesTask::MODULE_SEPARATOR) + 1);
			$branch = substr($branch, 0, strpos($branch, LoadModulesTask::MODULE_SEPARATOR));
		}

		$currentBranch = trim($this->callingTask->exec("cd $moduleName && git branch && cd \"$currentDir\"", true));

		$overwriteOpt = $overwrite ? '-f' : '';

		$this->callingTask->exec("cd $this->name && git checkout $overwriteOpt $branch && git pull origin $branch && cd \"$currentDir\"");

		if ($commitId) {
			$this->callingTask->exec("cd $this->name && git pull && git checkout $commitId && cd \"$currentDir\"");
		}
	}
	
}

class LoadModulesTask_SubversionLoader extends LoadModulesTask_Loader {
	
	function checkout($storeLocally = false) {
		$revision = '';
		if ($this->branch != 'master') {
			$revision = " --revision $this->branch ";
		}
		
		$cmd = ($storeLocally) ? 'export' : 'co';
		$this->callingTask->exec("svn $cmd $revision $this->url $this->name");
	}
	
	function update($overwrite = true) {
		$branch = $this->branch;
		$currentDir = getcwd();
		
		$revision = '';
		if ($branch != 'master') {
			$revision = " --revision $branch ";
		}

		echo $this->callingTask->exec("svn up $revision $this->name");
	}
	
	function getModifiedFiles() {
		$currentDir = getcwd();
		$statCmd = "svn stat";
		return trim($this->callingTask->exec("cd $this->module && $statCmd && cd \"$currentDir\"", true));
	}
	
}

class LoadModulesTask_PistonLoader extends LoadModulesTask_Loader {
	
	function __construct($callingTask, $name, $url, $branch = null) {
		parent::__construct($callingTask, $name, $url, $branch);
		
		if(strpos($branch, ':') !== FALSE) {
			throw new BuildException(sprintf('Git tags not supported by piston'));
		}
	}
	
	function update($overwrite = true) {
		$currentDir = getcwd();		
		$revision = ($this->branch != 'master') ? " --commit $this->branch " : '';
		$overwriteOpts = ($overwrite) ? '--force' : '';
		echo $this->callingTask->exec("piston update $overwriteOpts $revision $this->name");
		
		$this->callingTask->log(sprintf('Updated "$this->name" via piston, please don\'t forget to commit any changes'));
	}
	
	function checkout($storeLocally = false) {
		$git = strrpos($this->url, '.git') == (strlen($this->url) - 4);
		$revision = ($this->branch != 'master') ? " --commit $this->branch " : '';
		$type = ($git) ? 'git' : 'subversion';
		$this->callingTask->exec("piston import --repository-type $type $revision $this->url $this->name");
		
		$this->callingTask->log(sprintf('Created "$this->name" via piston, please don\'t forget to commit any changes'));
	}
	
	/**
	 * @todo Check base working copy if not dealing with flattened directory.
	 */
	function getModifiedFiles() {
		return '';
	}
}