<?php

/**
 * Scans a folder and its subfolders and returns all the git repositories contained within
 * @author jseide
 *
 */
class FindRepositoriesTask extends Task {
	private $items = null;
	private $targetDir = null;
	private $copy = false;
	private $sourceDir = null;


	public function setTargetDir($targetDir) {
		$this->targetDir = $targetDir;
	}

	/**
	 * Recursively lists a folder and includes only those directories that have the filter parameter as a sub-item
	 */
	protected function recursiveListDirFilter($dir, &$result, $filter = '.git') {
		$dir = realpath($dir);

		// open this directory
		if ($handle = opendir($dir)) {

			// get each git entry
			while (false !== ($file = readdir($handle))) {
				if ($file == "." || $file == "..") continue;
				//var_dump($file);
				if ($file == '.git' && is_dir($file))  {
					if (file_exists($dir.'/'.$file.'/HEAD')) {
						$result[] = $dir;   //$dir is a git repository
					}
				} else {
					$path = $dir.'/'.$file;
					if (is_dir($path)) {
						$this->recursiveListDirFilter($path, $result, $filter);
					}
				}
			}
		}

		// close directory
		closedir($handle);

		return $result;
	}

	public function main() {
		if (!is_dir($this->targetDir)) {
			throw new BuildException("Invalid target directory: $this->targetDir");
		}

		$gitDirs = array();
		$this->recursiveListDirFilter($this->targetDir, $gitDirs, '.git');
		$this->project->setProperty('GitReposList',implode(',',$gitDirs));
	}
}

?>