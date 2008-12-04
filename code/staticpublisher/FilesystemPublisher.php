<?php

/**
 * Usage: Object::add_extension("SiteTree", "FilesystemPublisher('../static-folder/')")
 */
class FilesystemPublisher extends StaticPublisher {
	protected $destFolder;
	protected $fileExtension;
	
	protected static $static_base_url = null;
	
	/**
	 * Set a different base URL for the static copy of the site.
	 * This can be useful if you are running the CMS on a different domain from the website.
	 */
	static function set_static_base_url($url) {
		self::$static_base_url = $url;
	}
	
	/**
	 * @param $destFolder The folder to save the cached site into
	 * @param $fileExtension  The file extension to use, for example, 'html'.  If omitted, then each page will be placed
	 * in its own directory, with the filename 'index.html'
	 */
	function __construct($destFolder, $fileExtension = null) {
		if(substr($destFolder, -1) == '/') $destFolder = substr($destFolder, 0, -1);
		$this->destFolder = $destFolder;
		$this->fileExtension = $fileExtension;
	}
	
	function publishPages($urls) {
		set_time_limit(0);
		ini_set("memory_limit" , -1);
		
		//$base = Director::absoluteURL($this->destFolder);
		//$base = preg_replace('/\/[^\/]+\/\.\./','',$base) . '/';
		
		if(self::$static_base_url) Director::setBaseURL(self::$static_base_url);
		
		$files = array();
		$i = 0;
		$totalURLs = sizeof($urls);
		foreach($urls as $url) {
			$i++;
			
			if(StaticPublisher::echo_progress()) {
				echo " * Publishing page $i/$totalURLs: $url\n";
				flush();
			}
			
			Requirements::clear();
			$response = Director::test($url);
			Requirements::clear();
			/*
			if(!is_object($response)) {
				echo "String response for url '$url'\n";
				print_r($response);
			}*/
			if(is_object($response)) $content = $response->getBody();
			else $content = $response . '';

			if($this->fileExtension) $filename = $url ? "$url.$this->fileExtension" : "index.$this->fileExtension";
			else $filename = $url ? "$url/index.html" : "index.html";
				
			$files[$filename] = array(
				'Content' => $content,
				'Folder' => (dirname($filename) == '/') ? '' :  (dirname($filename).'/'),
				'Filename' => basename($filename),
			);
			
			// Add externals
			/*
			$externals = $this->externalReferencesFor($content);
			if($externals) foreach($externals as $external) {
				// Skip absolute URLs
				if(preg_match('/^[a-zA-Z]+:\/\//', $external)) continue;
				// Drop querystring parameters
				$external = strtok($external, '?');
				
				if(file_exists("../" . $external)) {
					// Break into folder and filename
					if(preg_match('/^(.*\/)([^\/]+)$/', $external, $matches)) {
						$files[$external] = array(
							"Copy" => "../$external",
							"Folder" => $matches[1],
							"Filename" => $matches[2],
						);
					
					} else {
						user_error("Can't parse external: $external", E_USER_WARNING);
					}
				} else {
					$missingFiles[$external] = true;
				}
			}*/
		}

		if(self::$static_base_url) Director::setBaseURL(null);

		//Debug::show(array_keys($files));
		//Debug::show(array_keys($missingFiles));
		
		$base = "../$this->destFolder";
		foreach($files as $file) {
			Filesystem::makeFolder("$base/$file[Folder]");
			
			if(isset($file['Content'])) {
				$fh = fopen("$base/$file[Folder]$file[Filename]", "w");
				fwrite($fh, $file['Content']);
				fclose($fh);
			} else if(isset($file['Copy'])) {
				copy($file['Copy'], "$base/$file[Folder]$file[Filename]");
			}
		}
	}
}

?>