<?php

/**
Interfaces to various external modules or binaries
*/

class HTTP {
	static function available() {
		return function_exists('curl_init');
	}
	
	static function get($url, $dst = null) {
		$hndl = curl_init($url); $fhndl = null;
		
		curl_setopt($hndl, CURLOPT_FOLLOWLOCATION, true);
		// Unfortunately, ssl isn't set up right by default in php for windows
		if (strpos(PHP_OS, "WIN") !== false) curl_setopt($hndl, CURLOPT_SSL_VERIFYPEER, false);
		
		if ($dst) {
			$fhndl = fopen($dst, 'wb');
			curl_setopt($hndl, CURLOPT_FILE, $fhndl);
		}
		else {
			curl_setopt($hndl, CURLOPT_RETURNTRANSFER, true);
		}
		
		$res = curl_exec($hndl);
		if (!$res) {
			throw new Exception("Downloading ".$url." failed - curl says: ".curl_error($hndl));
		}
		
		curl_close($hndl);
		if ($fhndl) fclose($fhndl);
		
		return $res;
	}
}

class SVN {
	static function available() {
		exec('svn --version', $out, $rv);
		return $rv === 0;
	}
	
	static function isSVNRepo() {
		return is_dir('.svn');
	}
	
	static function export($repo, $out) {
		`svn export $repo $out`;
	}
	
	static function checkout($repo, $out) {
		`svn checkout $repo $out`;
	}
}

class GIT {
	static function available() {
		exec('git --version', $out, $rv);
		return $rv === 0;
	}
	
	static function isGITRepo() {
		return is_dir('.git');
	}
	
	static function add($dir) {
		`git add $dir`;
	}
	
	static function ignore($dir) {
		$hndl = fopen('.gitignore', 'a');
		fwrite($hfnl, $dir."\n");
		fclose($hndl);
	}
	
	static function checkout($repo, $branch, $out) {
		if ($branch) `git clone -b $branch $repo $out`;
		else `git clone -b $branch $repo $out`;
	}
}

class Piston {
	static function available() {
		exec('piston --version', $out, $rv);
		return $rv === 0;
	}
	
	static function import($src, $branch, $dest) {
		if ($branch) `piston import --commit $branch $src $dest`;
		else `piston import $src $dest`;
	}
}

class Zip {
	static function available() {
		return class_exists('ZipArchive');
	}
	
	static function import($src, $dest, $skipdirs = 0, $subdir = null) {
		$zip = new ZipArchive;
		$res = $zip->open($src);
		if ($res === TRUE) {
			
			if ($skipdirs) {
				$tmpdir = tempnam(sys_get_temp_dir(), 'phpinstaller-') . '.ext';
				mkdir($tmpdir, 0700);
			
				mkdir($dest);
			
				$zip->extractTo($tmpdir);
				
				for($i = 0; $i < $zip->numFiles; $i++){  
					$name = $srcname = $zip->getNameIndex($i);
					$parts = array();
				
					while ($name && $name != '.' && $name != '/') {
						array_unshift($parts, basename($name));
						$name = dirname($name);
					}
					
					if ($subdir) {
						// We only need to move the level after the level after the skipdirs level, presuming that level after the skipdirs level == $subdir
						if (count($parts) != $skipdirs+2) continue;
						if ($parts[$skipdirs] != $subdir) continue;
						
						$dstname = $parts[$skipdirs+1];
					}
					else {
						// We only need to move the very next level after the skipdirs level
						if (count($parts) != $skipdirs+1) continue;
					
						$dstname = $parts[$skipdirs];
					}
					
					rename($tmpdir.'/'.$srcname, $dest.'/'.$dstname);
				}
			}
			else {
				$zip->extractTo($dest);
			}
			
		    $zip->close();
		} else {
			throw new Exception('Could not extract zip at '.$src.' to '.$dest);
		}
	}
}

