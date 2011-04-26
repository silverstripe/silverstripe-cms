<?php

include_once dirname(__FILE__) . '/SilverStripeBuildTask.php';

/**
 * A phing task to load modules from a specific URL via SVN or git checkouts
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
		$this->configureEnvFile();

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
				$svnUrl = trim($bits[1], '/');
				$storeLocally = false;
				
				if (isset($bits[2])) {
					$devBuild = $bits[2] == 'true';
					$storeLocally = $bits[2] == 'local';
					if (isset($bits[3])) {
						$storeLocally = $bits[3] == 'local';
					}
				}

				$this->loadModule($moduleName, $svnUrl, $devBuild, $storeLocally);
			}
		}
	}

	/**
	 * Actually load the module!
	 *
	 * @param String $moduleName
	 * @param String $svnUrl
	 * @param boolean $devBuild
	 * 			Do we run a dev/build?
	 * @param boolean $storeLocally
	 *			Should we store the module locally, for it to be included in 
	 *			the local project's repository?
	 */
	protected function loadModule($moduleName, $svnUrl, $devBuild = false, $storeLocally=false) {
		$git = strrpos($svnUrl, '.git') == (strlen($svnUrl) - 4);
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

		// check the module out if it doesn't exist
		$currentDir = trim(`pwd`," \n");
		if (!file_exists($moduleName)) {
			echo "Check out $moduleName from $svnUrl\n";
			// check whether it's git or svn
			if ($git) {
				$this->exec("git clone $svnUrl $moduleName");
				if ($branch != 'master') {
					// check if we're also hooking onto a revision
					$commitId = null;
					if (strpos($branch, self::MODULE_SEPARATOR) > 0) {
						$commitId = substr($branch, strpos($branch, self::MODULE_SEPARATOR) + 1);
						$branch = substr($branch, 0, strpos($branch, self::MODULE_SEPARATOR));
					}
					// need to make sure we've pulled from the correct branch also
					$currentDir = trim(`pwd`," \n");
					if ($branch != 'master') {
						$this->exec("cd $moduleName && git checkout -f -b $branch --track origin/$branch && cd \"$currentDir\"");
					}

					if ($commitId) {
						$this->exec("cd $moduleName && git checkout $commitId && cd \"$currentDir\"");
					}
				}
				
				if ($storeLocally) {
					rrmdir("$moduleName/.git");
				}
			} else {
				$revision = '';
				if ($branch != 'master') {
					$revision = " --revision $branch ";
				}
				
				$cmd = 'co';
				if ($storeLocally) {
					$cmd = 'export';
				}

				$this->exec("svn $cmd $revision $svnUrl $moduleName");
			}

			// make sure to append it to the .gitignore file
			if (!$storeLocally && file_exists('.gitignore')) {
				$gitIgnore = file_get_contents('.gitignore');
				if (strpos($gitIgnore, $moduleName) === false) {
					$this->exec("echo $moduleName >> .gitignore");
				}
			}
		} else {
			echo "Updating $moduleName $branch from $svnUrl\n";
			
			$overwrite = true;
			if (!$storeLocally) {
				$statCmd = $git ? "git diff --name-status" : "svn status";
				$mods = trim($this->exec("cd $moduleName && $statCmd && cd \"$currentDir\"", true));
				if (strlen($mods) && !$storeLocally) {
					$this->log("The following files are locally modified");
					echo "\n $mods\n\n";
					if (!$this->nonInteractive) {
						$overwrite = strtolower(trim($this->getInput("Overwrite local changes? [y/N]")));
						$overwrite = $overwrite == 'y';
					} 
				}
			}
			
			// get the metadata and make sure it's not the same
			if ($md && isset($md[$moduleName]) && isset($md[$moduleName]['url'])) {
				if ($md[$moduleName]['url'] != $svnUrl || $md[$moduleName]['store'] != $storeLocally) {
					if ($overwrite) {
					// delete the directory and reload the module
						echo "Deleting $moduleName and reloading\n";
						unset($md[$moduleName]);
						$this->writeMetadata($md);
						rrmdir($moduleName, true);
						$this->loadModule($originalName, $svnUrl, $devBuild, $storeLocally);
						return;
					} else {
						throw new Exception("You have chosen not to overwrite changes, but also want to change your " .
							"SCM settings. Please resolve changes and try again");
					}
				}
			}

			if (!$storeLocally) {
				if ($git) {
					$commitId = null;
					if (strpos($branch, self::MODULE_SEPARATOR) > 0) {
						$commitId = substr($branch, strpos($branch, self::MODULE_SEPARATOR) + 1);
						$branch = substr($branch, 0, strpos($branch, self::MODULE_SEPARATOR));
					}

					$currentDir = trim(`pwd`," \n");

					$currentBranch = trim($this->exec("cd $moduleName && git branch && cd \"$currentDir\"", true));

					$overwriteOpt = $overwrite ? '-f' : '';

					$this->exec("cd $moduleName && git checkout $overwriteOpt $branch && git pull origin $branch && cd \"$currentDir\"");

					if ($commitId) {
						$this->exec("cd $moduleName && git pull && git checkout $commitId && cd \"$currentDir\"");
					}
				} else {
					$revision = '';
					if ($branch != 'master') {
						$revision = " --revision $branch ";
					}

					echo $this->exec("svn up $revision $moduleName");
				}
			}
		}

		$metadata = array(
			'url' => $svnUrl,
			'store' => $storeLocally,
			'branch' => str_replace($moduleName, '', $originalName),
		);

		$md[$moduleName] = $metadata;
		$this->writeMetadata($md);
		
		
		
		// make sure to remove from the .gitignore file - don't need to do it EVERY 
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

		if ($devBuild) {
			$this->devBuild();
		}
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
