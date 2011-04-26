<?php
include_once dirname(__FILE__) . '/SilverStripeBuildTask.php';

/**
 * Returns changelogs for the git (or svn) repositories specified in the changelog-definitions file
 *
 * @author jseide
 *
 */
class CreateChangelog extends SilverStripeBuildTask {

	protected $definitions = null;
	protected $baseDir = null;
	protected $sort = 'type';
	protected $filter = null;

	public function setDefinitions($definitions) {
		$this->definitions  = $definitions;
	}

	public function setBaseDir($base) {
		$this->baseDir = $base;
	}

	public function setSort($sort) {
		$this->sort = $sort;
	}

	public function setFilter($filter) {
		$this->filter = $filter;
	}

	/**
	 * Checks is a folder is a version control repository
	 */
	protected function isRepository($dir, $filter) {
		$dir = realpath($dir);

		// open this directory
		if ($handle = opendir($dir)) {

			// get each file
			while (false !== ($file = readdir($handle))) {
				if ($file == $filter && is_dir($file))  {
					if ($filter == '.git') {    //extra check for git repos
						if (file_exists($dir.'/'.$file.'/HEAD')) {
							return true;   //$dir is a git repository
						}
					} else {    //return true for .svn repos
						return true;
					}
				}
			}
		}

		return false;
	}

	protected function isGitRepo($dir) {
		return $this->isRepository($dir, '.git');
	}

	protected function isSvnRepo($dir) {
		return $this->isRepository($dir, '.svn');
	}

	protected function gitLog($path, $from = null, $to = null) {
		//set the from -> to range, depending on which os these have been set
		if ($from && $to) $range = " $from..$to";
		elseif ($from) $range = " $from..HEAD";
		else $range = "";

		$log = $this->exec("git log --pretty=tformat:\"%s (%aN) [%h]\"{$range} {$path}", true);    //return output of command
		return $log;
	}

	/** Sort by the first two letters of the commit string.
	 *  Put any commits without BUGFIX, ENHANCEMENT, etc. at the end of the list
	 */
	static function sortByType($a, $b) {
		if (strlen($a) >= 2) $a = substr($a,0,2);
		if (strlen($b) >= 2) $b = substr($b,0,2);

		if (empty($b)) return -1;   //put them at the end of the commit list
		if (is_numeric($b)) return -1;
		if (self::islower($b)) return -1;
		if (!self::isupper($b)) return -1;

		if (empty($a)) return +1;
		if (self::islower($a)) return +1;
		if (!self::isupper($a)) return +1;


        if ($a == $b) {
            return 0;
        }
        return ($a > $b) ? +1 : -1;
	}

	/** BETTER SORTING FUNCTION: Sort by the first two letters of the commit string.
	 *  Put any commits without BUGFIX, ENHANCEMENT, etc. at the end of the list
	 */
	static function sortByType2($array) {
		$bugfixes = array();
		$enhancements = array();
		$apichanges = array();
		$features = array();
		$minors = array();
		$others = array();

		foreach($array as $ele) {
			if (strlen($ele) >= 2) $ele1 = substr($ele,0,2); else $ele1 = $ele;

			if ($ele1 == "BU") $bugfixes[] = $ele;
			elseif ($ele1 == "EN") $enhancements[] = $ele;
			elseif ($ele1 == "AP") $apichanges[] = $ele;
			elseif ($ele1 == "FE") $features[] = $ele;
			elseif ($ele1 == "MI") $minors[] = $ele;
			elseif ($ele1 == "") ; //discard empty commit messages
			else $others[] = $ele;
		}

		return array_merge(array("# API Changes"), $apichanges,
		                   array("","# Features & Enhancements"), $features, $enhancements,
		                   array("","# Bugfixes"),$bugfixes,
		                   array("","# Minors"), $minors,
		                   array("","# Others"), $others);
	}

	static function isupper($i) {
		return (strtoupper($i) === $i);
	}
	static function islower($i) {
		return (strtolower($i) === $i);
	}

	public function main() {
		chdir($this->baseDir);  //change current working directory

		//parse the definitions file
		$items = file($this->definitions);
		$repos = array();   //git (or svn) repos to scan
		foreach ($items as $item) {
			$item = trim($item);
			if (strpos($item, '#') === 0) {
				continue;
			}

			$bits = preg_split('/\s+/', $item);

			if (count($bits) == 1) {
				$repos[$bits[0]] = "";
			} elseif (count($bits) == 2) {
				$repos[$bits[0]] = array($bits[1], null);    //sapphire => array(from => HEAD)
			} elseif (count($bits) == 3) {
				$repos[$bits[0]] = array($bits[1],$bits[2]);    //sapphire => array(from => to)
			} else {
				continue;
			}
		}

		//check all the paths are valid git repos
		$gitRepos = array();
		$svnRepos = array();
		foreach($repos as $path => $range) {
			if ($this->isGitRepo($path)) $gitRepos[$path] = $range; //add all git repos to a special array
			//TODO: for svn support use the isSvnRepo() method to add repos to the svnRepos array
		}

		//run git log (with author information)
		$log = array();
		foreach($gitRepos as $path => $range) {
			if (!empty($range)) {
				$from = (isset($range[0])) ? $range[0] : null;
				$to = (isset($range[1])) ? $range[1] : null;
				$log[$path] = $this->gitLog($path, $from, $to);
			} else {
				$log[$path] = $this->gitLog($path);
			}
		}

		//merge all the changelogs together
		$mergedLog = array();
		foreach($log as $path => $commitsString) {
			foreach(explode("\n",$commitsString) as $commit) {  //array from newlines
				$mergedLog[] = $commit;
			}
		}


		//sort the output (based on params), grouping
		if ($this->sort == 'type') {    //sort by type, i.e. first two letters
			$mergedLog = $this->sortByType2($mergedLog);
		} else {
			//leave as sorted by default order
		}

		//filter out commits we don't want
		if ($this->filter) {
			foreach($mergedLog as $key => $item) {
				if (preg_match($this->filter, $item)) unset($mergedLog[$key]);
			}
		}

		//convert to string
		//and generate markdown (add list to beginning of each item)
		$output = "";
		foreach($mergedLog as $logMessage) {
			$firstCharacter = substr($logMessage,0,1);
			if ($firstCharacter != "#" && $firstCharacter != "") $output .= "- $logMessage\n";
			else $output .= "$logMessage\n";
		}




		$this->project->setProperty('changelogOutput',$output);
	}
}

?>