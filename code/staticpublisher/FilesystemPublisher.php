<?php

/**
 * Usage: Object::add_extension("SiteTree", "FilesystemPublisher('static-folder', 'html')");
 * 
 * Usage: To work with Subsite module you need to:
 * - Add FilesystemPublisher::$domain_based_caching = true; in mysite/_config.php
 * - Added main site host mapping in subsites/host-map.php after everytime a new subsite is created or modified 
 *
 * You may also have a method $page->pagesAffectedByUnpublishing() to return other URLS
 * that should be de-cached if $page is unpublished.
 *
 * @see http://doc.silverstripe.com/doku.php?id=staticpublisher
 * 
 * @package cms
 * @subpackage publishers
 */
class FilesystemPublisher extends StaticPublisher {

	/**
	 * @var String
	 */
	protected $destFolder = 'cache';
	
	/**
	 * @var String
	 */
	protected $fileExtension = 'html';
	
	/**
	 * @var String
	 */
	protected static $static_base_url = null;
	
	/**
	 * @var Boolean Use domain based cacheing (put cache files into a domain subfolder)
	 * This must be true if you are using this with the "subsites" module.
	 * Please note that this form of caching requires all URLs to be provided absolute
	 * (not relative to the webroot) via {@link SiteTree->AbsoluteLink()}.
	 */
	public static $domain_based_caching = false;
	
	/**
	 * Set a different base URL for the static copy of the site.
	 * This can be useful if you are running the CMS on a different domain from the website.
	 */
	static function set_static_base_url($url) {
		self::$static_base_url = $url;
	}
	
	/**
	 * @param $destFolder The folder to save the cached site into.
	 *   This needs to be set in framework/static-main.php as well through the {@link $cacheBaseDir} variable.
	 * @param $fileExtension  The file extension to use, e.g 'html'.  
	 *   If omitted, then each page will be placed in its own directory, 
	 *   with the filename 'index.html'.  If you set the extension to PHP, then a simple PHP script will
	 *   be generated that can do appropriate cache & redirect header negotation.
	 */
	function __construct($destFolder = 'cache', $fileExtension = null) {
		// Remove trailing slash from folder
		if(substr($destFolder, -1) == '/') $destFolder = substr($destFolder, 0, -1);
		
		$this->destFolder = $destFolder;
		$this->fileExtension = $fileExtension;
		
		parent::__construct();
	}
	
	/**
	 * Transforms relative or absolute URLs to their static path equivalent.
	 * This needs to be the same logic that's used to look up these paths through
	 * framework/static-main.php. Does not include the {@link $destFolder} prefix.
	 * 
	 * URL filtering will have already taken place for direct SiteTree links via SiteTree->generateURLSegment()).
	 * For all other links (e.g. custom controller actions), we assume that they're pre-sanitized
	 * to suit the filesystem needs, as its impossible to sanitize them without risking to break
	 * the underlying naming assumptions in URL routing (e.g. controller method names).
	 * 
	 * Examples (without $domain_based_caching):
	 *  - http://mysite.com/mywebroot/ => /index.html (assuming your webroot is in a subfolder)
	 *  - http://mysite.com/about-us => /about-us.html
	 *  - http://mysite.com/parent/child => /parent/child.html
	 * 
	 * Examples (with $domain_based_caching):
	 *  - http://mysite.com/mywebroot/ => /mysite.com/index.html (assuming your webroot is in a subfolder)
	 *  - http://mysite.com/about-us => /mysite.com/about-us.html
	 *  - http://myothersite.com/about-us => /myothersite.com/about-us.html
	 *  - http://subdomain.mysite.com/parent/child => /subdomain.mysite.com/parent/child.html
	 * 
	 * @param Array $urls Absolute or relative URLs
	 * @return Array Map of original URLs to filesystem paths (relative to {@link $destFolder}).
	 */
	function urlsToPaths($urls) {
		$mappedUrls = array();
		foreach($urls as $url) {

			// parse_url() is not multibyte safe, see https://bugs.php.net/bug.php?id=52923.
			// We assume that the URL hsa been correctly encoded either on storage (for SiteTree->URLSegment),
			// or through URL collection (for controller method names etc.).
			$urlParts = @parse_url($url);
			
			// Remove base folders from the URL if webroot is hosted in a subfolder (same as static-main.php)
			$path = isset($urlParts['path']) ? $urlParts['path'] : '';
			if(mb_substr(mb_strtolower($path), 0, mb_strlen(BASE_URL)) == mb_strtolower(BASE_URL)) {
				$urlSegment = mb_substr($path, mb_strlen(BASE_URL));
			} else {
				$urlSegment = $path;
			}

			// Normalize URLs
			$urlSegment = trim($urlSegment, '/');

			$filename = $urlSegment ? "$urlSegment.$this->fileExtension" : "index.$this->fileExtension";

			if (self::$domain_based_caching) {
				if (!$urlParts) continue; // seriously malformed url here...
				$filename = $urlParts['host'] . '/' . $filename;
			}
		
			$mappedUrls[$url] = ((dirname($filename) == '/') ? '' :  (dirname($filename).'/')).basename($filename);
		}

		return $mappedUrls;
	}
	
	function unpublishPages($urls) {
		// Do we need to map these?
		// Detect a numerically indexed arrays
		if (is_numeric(join('', array_keys($urls)))) $urls = $this->urlsToPaths($urls);
		
		// This can be quite memory hungry and time-consuming
		// @todo - Make a more memory efficient publisher
		increase_time_limit_to();
		increase_memory_limit_to();
		
		$cacheBaseDir = $this->getDestDir();
		
		foreach($urls as $url => $path) {
			if (file_exists($cacheBaseDir.'/'.$path)) {
				@unlink($cacheBaseDir.'/'.$path);
			}
		}
	}
	
	function publishPages($urls) { 
		// Do we need to map these?
		// Detect a numerically indexed arrays
		if (is_numeric(join('', array_keys($urls)))) $urls = $this->urlsToPaths($urls);
		
		// This can be quite memory hungry and time-consuming
		// @todo - Make a more memory efficient publisher
		increase_time_limit_to();
		increase_memory_limit_to();
		
		// Set the appropriate theme for this publication batch.
		// This may have been set explicitly via StaticPublisher::static_publisher_theme,
		// or we can use the last non-null theme.
		if(!StaticPublisher::static_publisher_theme())
			SSViewer::set_theme(SSViewer::current_custom_theme());
		else
			SSViewer::set_theme(StaticPublisher::static_publisher_theme());
			
		$currentBaseURL = Director::baseURL();
		if(self::$static_base_url) Director::setBaseURL(self::$static_base_url);
		if($this->fileExtension == 'php') SSViewer::setOption('rewriteHashlinks', 'php'); 
		if(StaticPublisher::echo_progress()) echo $this->class.": Publishing to " . self::$static_base_url . "\n";		
		$files = array();
		$i = 0;
		$totalURLs = sizeof($urls);

		foreach($urls as $url => $path) {
			
			if(self::$static_base_url) Director::setBaseURL(self::$static_base_url);
			$i++;

			if($url && !is_string($url)) {
				user_error("Bad url:" . var_export($url,true), E_USER_WARNING);
				continue;
			}
			
			if(StaticPublisher::echo_progress()) {
				echo " * Publishing page $i/$totalURLs: $url\n";
				flush();
			}

			Requirements::clear();
			
			if($url == "") $url = "/";
			if(Director::is_relative_url($url)) $url = Director::absoluteURL($url);
			$response = Director::test(str_replace('+', ' ', $url));
			
			Requirements::clear();
			
			singleton('DataObject')->flushCache();

			//skip any responses with a 404 status code. We don't want to turn those into statically cached pages
			if (!$response || $response->getStatusCode() == '404') continue;

			// Generate file content			
			// PHP file caching will generate a simple script from a template
			if($this->fileExtension == 'php') {
				if(is_object($response)) {
					if($response->getStatusCode() == '301' || $response->getStatusCode() == '302') {
						$content = $this->generatePHPCacheRedirection($response->getHeader('Location'));
					} else {
						$content = $this->generatePHPCacheFile($response->getBody(), HTTP::get_cache_age(), date('Y-m-d H:i:s'));
					}
				} else {
					$content = $this->generatePHPCacheFile($response . '', HTTP::get_cache_age(), date('Y-m-d H:i:s'));
				}
				
			// HTML file caching generally just creates a simple file
			} else {
				if(is_object($response)) {
					if($response->getStatusCode() == '301' || $response->getStatusCode() == '302') {
						$absoluteURL = Director::absoluteURL($response->getHeader('Location'));
						$content = "<meta http-equiv=\"refresh\" content=\"2; URL=$absoluteURL\">";
					} else {
						$content = $response->getBody();
					}
				} else {
					$content = $response . '';
				}
			}
			
			$files[] = array(
				'Content' => $content,
				'Folder' => dirname($path).'/',
				'Filename' => basename($path),
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

		if(self::$static_base_url) Director::setBaseURL($currentBaseURL); 
		if($this->fileExtension == 'php') SSViewer::setOption('rewriteHashlinks', true); 

		$base = BASE_PATH . "/$this->destFolder";
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
	
	/**
	 * Generate the templated content for a PHP script that can serve up the given piece of content with the given age and expiry
	 */
	protected function generatePHPCacheFile($content, $age, $lastModified) {
		$template = file_get_contents(BASE_PATH . '/cms/code/staticpublisher/CachedPHPPage.tmpl');
		return str_replace(
				array('**MAX_AGE**', '**LAST_MODIFIED**', '**CONTENT**'),
				array((int)$age, $lastModified, $content),
				$template);
	}
	/**
	 * Generate the templated content for a PHP script that can serve up a 301 redirect to the given destionation
	 */
	protected function generatePHPCacheRedirection($destination) {
		$template = file_get_contents(BASE_PATH . '/cms/code/staticpublisher/CachedPHPRedirection.tmpl');
		return str_replace(
				array('**DESTINATION**'),
				array($destination),
				$template);
	}
	
	public function getDestDir() {
		return BASE_PATH . '/' . $this->destFolder;
	}
	
	/**
	 * Return an array of all the existing static cache files, as a map of URL => file.
	 * Only returns cache files that will actually map to a URL, based on urlsToPaths.
	 */
	public function getExistingStaticCacheFiles() {
		$cacheDir = BASE_PATH . '/' . $this->destFolder;

		$urlMapper = array_flip($this->urlsToPaths($this->owner->allPagesToCache()));
		
		$output = array();
		
		// Glob each dir, then glob each one of those
		foreach(glob("$cacheDir/*", GLOB_ONLYDIR) as $cacheDir) {
			foreach(glob($cacheDir.'/*') as $cacheFile) {
				$mapKey = str_replace(BASE_PATH . "/cache/","",$cacheFile);
				if(isset($urlMapper[$mapKey])) {
					$url = $urlMapper[$mapKey];
					$output[$url] = $cacheFile;
				}
			}
		}
		
		return $output;
	}
	
}

