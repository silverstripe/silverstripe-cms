<?php

/**
Sources define a set of instructions for exporting, pistoning or checking out code from some sort of repository
*/

class GitRepo {
	function __construct($data) {
		$this->data = $data;
	}
	
	function repoURL() {
		return $this->data['repo'];
	}
	
	function export($out) {
		throw new Exception('Dont know how to do this yet');
	}

	function canExport() {
		return array('Cant currently use flat mode with git source repositories');
	}
	
	function piston($out) {
		$data = $this->data;
		Piston::import($this->repoURL(), $data['branch'], $out);
	}

	function canPiston() {
		$errors = array();
		if (!GIT::available()) $errors = "Git is not available.";
		if (!Piston::available()) $errors[] = "Piston is not available.";
		if (!SVN::isSVNRepo() && !GIT::isGITRepo()) $errors[] = "Piston only works on svn working copies and git repositories.";
		return $errors;
	}
	
	function checkout($out) {
		$data = $this->data;
		GIT::checkout($this->repoURL(), $data['branch'], $out);
	}

	function canCheckout() {
		$errors = array();
		if (!GIT::available()) $errors = "Git is not available.";
		return $errors;
	}
}

class Github extends GitRepo {
	protected $data;
	
	function __construct($data) {
		$this->data = $data;
	}
	
	function repoURL() {
		$data = $this->data;
		return "git://github.com/{$data['user']}/{$data['project']}.git";
	}
	
	function export($out) {
		$data = $this->data;
		
		$tmp = tempnam(sys_get_temp_dir(), 'phpinstaller-') . '.zip';
		
		HTTP::get("https://github.com/{$data['user']}/{$data['project']}/zipball/{$data['branch']}", $tmp);
		Zip::import($tmp, $out, 1);		
	}
	
	function canExport() {
		$errors = array();
		if (!HTTP::available()) $errors[] = "The curl module is not available";
		if (!Zip::available()) $errors[] = "The zip module is not available";
		return $errors;
	}
}

class GithubSparse extends Github {
	function piston($out) {
		$this->export($out);
		if (Git::isGITRepo()) Git::add($out);
	}
	
	function canPiston() {
		$data = $this->data;
		echo "WARNING: Sparse import of directory {$data['subdir']} from {$this->repoURL()} will be flat, not pistoned\n";
		return $this->canExport();
	}
	
	function checkout($out) {
		$this->export($out);
	}
	
	function canCheckout() {
		$data = $this->data;
		echo "WARNING: Sparse import of directory {$data['subdir']} from {$this->repoURL()} will be flat, not checked out\n";
		return $this->canExport();
	}
	
	function export($out) {
		$data = $this->data;
		
		$tmp = tempnam(sys_get_temp_dir(), 'phpinstaller-') . '.zip';
		
		HTTP::get("https://github.com/{$data['user']}/{$data['project']}/zipball/{$data['branch']}", $tmp);
		Zip::import($tmp, $out, 1, $data['subdir']);		
	}
}

class SvnRepo {
	function __construct($data) {
		$this->data = $data;
	}
	
	function repoURL() {
		$data = $this->data;
		return "{$data['repo']}/{$data['branch']}" . (isset($data['subdir']) ? "/{$data['subdir']}" : '');
	}
	
	function export($out) {
		SVN::export($this->repoURL(), $out);
	}
	
	function canExport() {
		$errors = array();
		if (!SVN::available()) $errors[] = "Subversion is not available.";
		return $errors;
	}
	
	function piston($out) {
		Piston::import($this->repoURL(), null, $out);
	}

	function canPiston() {
		$errors = array();
		if (!SVN::available()) $errors[] = "Subversion is not available.";
		if (!Piston::available()) $errors[] = "Piston is not available.";
		if (!SVN::isSVNRepo() && !GIT::isGITRepo()) $errors[] = "Piston only works on svn working copies and git repositories.";
		return $errors;
	}
	
	function checkout($out) {
		SVN::checkout($this->repoURL(), $out);
	}
	
	function canCheckout() {
		$errors = array();
		if (!SVN::available()) $errors[] = "Subversion is not available.";
		return $errors;
	}
}