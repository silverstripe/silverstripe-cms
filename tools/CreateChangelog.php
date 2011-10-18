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
	
	/**
	 * Order of the array keys determines order of the lists.
	 */
	public $types = array(
		'API Changes' => array('/^API CHANGE:?/i','/^APICHANGE?:?/i'),
		'Features and Enhancements' => array('/^(ENHANCEMENT|ENHNACEMENT):?/i', '/^FEATURE:?/i'),
		'Bugfixes' => array('/^(BUGFIX|BUGFUX):?/i','/^BUG FIX:?/i'),
		'Minor changes' => array('/^MINOR:?/i'),
		'Other' => array('/^[^A-Z][^A-Z][^A-Z]/') // dirty trick: check for uppercase characters
	);
	
	public $commitUrls = array(
		'.' => 'https://github.com/silverstripe/silverstripe-installer/commit/%s',
		'sapphire' => 'https://github.com/silverstripe/sapphire/commit/%s',
		'cms' => 'https://github.com/silverstripe/silverstripe-cms/commit/%s',
		'themes/blackcandy' => 'https://github.com/silverstripe-themes/silverstripe-blackcandy/commit/%s',
	);
	
	public $ignoreRules = array(
		'/^Merge/',
		'/^Blocked revisions/',
		'/^Initialized merge tracking /',
		'/^Created (branches|tags)/',
		'/^NOTFORMERGE/',
		'/^\s*$/'
	);

	public function setDefinitions($definitions) {
		$this->definitions  = $definitions;
	}

	public function setBaseDir($base) {
		$this->baseDir = realpath($base);
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
	protected function isRepository($dir_path, $filter) {
		$dir = $dir_path;

		if (file_exists($dir)) {
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

				echo "Folder '$dir' is not a $filter repository\n";
			}
		} else {
			echo "Folder '$dir' does not exist\n";
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

		chdir("$this->baseDir/$path");  //switch to the module's path

		// Internal serialization format, ideally this would be JSON but we can't escape characters in git logs.
		$log = $this->exec("git log --pretty=tformat:\"message:%s|||author:%aN|||abbrevhash:%h|||hash:%H|||date:%ad|||timestamp:%at\" --date=short {$range}", true);

		chdir($this->baseDir);  //switch the working directory back

		return $log;
	}
		
	/** Sort by the first two letters of the commit string.
	 *  Put any commits without BUGFIX, ENHANCEMENT, etc. at the end of the list
	 */
	function sortByType($commits) {
		$groupedByType = array();
		
		// sort by timestamp
		usort($commits, function($a, $b) {
			if($a['timestamp'] == $b['timestamp']) return 0;
			else return ($a['timestamp'] > $b['timestamp']) ? -1 : 1;
		});

		foreach($commits as $k => $commit) {
			// TODO
			// skip ignored revisions
			// if(in_array($commit['changeset'], $this->ignorerevisions)) continue;
			
			// Remove email addresses
			$commit['message'] = preg_replace('/(<?[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}>?)/mi', '', $commit['message']);
			
			// Condense git-style "From:" messages (remove preceding newline)
			if(preg_match('/^From\:/mi', $commit['message'])) {
				$commit['message'] = preg_replace('/\n\n^(From\:)/mi', ' $1', $commit['message']);
			}
			
			$matched = false;
			foreach($this->types as $name => $rules) {
				if(!isset($groupedByType[$name])) $groupedByType[$name] = array();
				foreach($rules as $rule) {
					if(!$matched && preg_match($rule, $commit['message'])) {
						// @todo The fallback rule on other can't be replaced, as it doesn't match a full prefix
						$commit['message'] = ($name != 'Other') ? trim(preg_replace($rule, '', $commit['message'])) : $commit['message'];
						$groupedByType[$name][] = $commit;
						$matched = true;
					}
				}
			}
			if(!$matched) {
				if(!isset($groupedByType['Other'])) $groupedByType['Other'] = array();
				$groupedByType['Other'][] = $commit;
			}
			
		}
		
		// // remove all categories which should be ignored
		// if($this->categoryIgnore) foreach($this->categoryIgnore as $categoryIgnore) {
		// 	if(isset($groupedByType[$categoryIgnore])) unset($groupedByType[$categoryIgnore]);
		// }

		return $groupedByType;
	}
	
	function commitToArray($commit) {
		$arr = array();
		$parts = explode('|||', $commit);
		foreach($parts as $part) {
			preg_match('/([^:]*)\:(.*)/', $part, $matches);
			$arr[$matches[1]] = $matches[2];
		}
		
		return $arr;
	}

	static function isupper($i) {
		return (strtoupper($i) === $i);
	}
	static function islower($i) {
		return (strtolower($i) === $i);
	}

	public function main() {
		error_reporting(E_ALL);
		
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

		//run git log
		$log = array();
		foreach($gitRepos as $path => $range) {
			$logForPath = array();
			if (!empty($range)) {
				$from = (isset($range[0])) ? $range[0] : null;
				$to = (isset($range[1])) ? $range[1] : null;
				$logForPath = explode("\n", $this->gitLog($path, $from, $to));
			} else {
				$logForPath = explode("\n", $this->gitLog($path));
			}
			foreach($logForPath as $commit) {
				if(!$commit) continue;
				$commitArr = $this->commitToArray($commit);
				$commitArr['path'] = $path;
				// Avoid duplicates by keying on hash
				$log[$commitArr['hash']] = $commitArr;
			}
		}

		// Remove ignored commits
		foreach($log as $k => $commit) {
			$ignore = false;
			foreach($this->ignoreRules as $ignoreRule) {
				if(preg_match($ignoreRule, $commit['message'])) {
					unset($log[$k]);
					continue;
				}
			}
		}

		//sort the output (based on params), grouping
		if ($this->sort == 'type') {
			$groupedLog = $this->sortByType($log);
		} else {
			//leave as sorted by default order
			$groupedLog = array('All' => $log);
		}

		//filter out commits we don't want
		// if ($this->filter) {
		// 	foreach($groupedLog as $key => $item) {
		// 		if (preg_match($this->filter, $item)) unset($groupedLog[$key]);
		// 	}
		// }

		//convert to string
		//and generate markdown (add list to beginning of each item)
		$output = "\n";
		foreach($groupedLog as $groupName => $commits) {
			if(!$commits) continue;
			
			$output .= "\n### $groupName\n\n";
			
			foreach($commits as $commit) {
				if(isset($this->commitUrls[$commit['path']])) {
					$hash = sprintf('[%s](%s)', 
						$commit['abbrevhash'],
						sprintf($this->commitUrls[$commit['path']], $commit['abbrevhash'])
					);
				} else {
					$hash = sprintf('[%s]', $commit['abbrevhash']);
				}
				$commitStr = sprintf('%s %s %s (%s)',
					$commit['date'],
					$hash,
					// Avoid rendering HTML in markdown
					str_replace(array('<', '>'), array('&lt;', '&gt;'), $commit['message']),
					$commit['author']
				);
				// $commitStr = sprintf($this->exec("git log --pretty=tformat:\"%s\" --date=short {$hash}^..{$hash}", true), $this->gitLogFormat);
				$output .= " * $commitStr\n";
			}
		}

		$this->project->setProperty('changelogOutput',$output);
	}
}

?>