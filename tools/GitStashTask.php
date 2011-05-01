<?php

/**
 * Git stashes a particular folder
 * @author jseide
 *
 */
class GitStashTask extends SilverStripeBuildTask {
	private $repository  = null;
	private $gitPath = null;
	private $pop = false;

	public function setRepository($repo) {
		$this->repository = $repo;
	}

	public function setGitPath($gitPath) {
		$this->gitPath = $gitPath;
	}

	public function setPop($pop) {
		$this->pop = $pop;
	}

	public function main() {
		if (!is_dir($this->repository)) {
			throw new BuildException("Invalid target directory: $this->repository");
		}

		$cwd = realpath(getcwd());

		chdir($this->repository);

		if ($this->pop == true) $result = parent::exec("$this->gitPath stash pop",true);
		else $result = parent::exec("$this->gitPath stash",true);

		if ($result) echo $result;

		chdir($cwd);
	}
}

?>