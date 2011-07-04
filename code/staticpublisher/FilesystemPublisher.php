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
	 *   This needs to be set in sapphire/static-main.php as well through the {@link $cacheBaseDir} variable.
	 * @param $fileExtension  The file extension to use, e.g 'html'.  
	 *   If omitted, then each page will be placed in its own directory, 
	 *   with the filename 'index.html'.  If you set the extension to PHP, then a simple PHP script will
	 *   be generated that can do appropriate cache & redirect header negotation.
	 */
	function __construct($destFolder, $fileExtension = null) {
		// Remove trailing slash from folder
		if(substr($destFolder, -1) == '/') $destFolder = substr($destFolder, 0, -1);
		
		$this->destFolder = $destFolder;
		$this->fileExtension = $fileExtension;
		
		parent::__construct();
	}
	
	/**
	 * Transforms relative or absolute URLs to their static path equivalent.
	 * This needs to be the same logic that's used to look up these paths through
	 * sapphire/static-main.php. Does not include the {@link $destFolder} prefix.
	 * Replaces various special characters in the resulting filename similar to {@link SiteTree::generateURLSegment()}.
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
			$urlParts = @parse_url($url);
			
			// Remove base folders from the URL if webroot is hosted in a subfolder (same as static-main.php)
			$path = isset($urlParts['path']) ? $urlParts['path'] : '';
			if(substr(strtolower($path), 0, strlen(BASE_URL)) == strtolower(BASE_URL)) {
				$urlSegment = substr($path, strlen(BASE_URL));
			} else {
				$urlSegment = $path;
			}

			// perform similar transformations to SiteTree::generateURLSegment()
			$urlSegment = str_replace('&amp;','-and-',$urlSegment);
			$urlSegment = str_replace('&','-and-',$urlSegment);
			$urlSegment = ereg_replace('[^A-Za-z0-9\/-]+','-',$urlSegment);
			$urlSegment = ereg_replace('-+','-',$urlSegment);
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
	
	/**
	 *
	 * @param array $urls 
	 */
	public function publishPages($urls) { 
		
		increase_time_limit_to();
		increase_memory_limit_to();
		
		if( !StaticPublisher::static_publisher_theme() ) {
			SSViewer::set_theme( SSViewer::current_custom_theme() );
		} else {
			SSViewer::set_theme( StaticPublisher::static_publisher_theme() );
		}
			
		$currentBaseURL = Director::baseURL();
		
		if( self::$static_base_url ) {
			Director::setBaseURL( self::$static_base_url );
		}
		
		if( $this->fileExtension == 'php' ) {
			SSViewer::setOption( 'rewriteHashlinks', 'php' );
		}
		
		if( StaticPublisher::echo_progress() ) {
			echo $this->class.": Publishing to " . self::$static_base_url . PHP_EOL;
		}
		
		// Detect a numerically indexed arrays
		if ( is_numeric( join( '', array_keys( $urls ) ) ) ) {
			$urls = $this->urlsToPaths( $urls );
		}
		
		$totalURLs = count( $urls );
		
		if( self::$static_base_url ) {
			Director::setBaseURL( self::$static_base_url );
		}
		
		$pagesProcessed = 0;
		foreach($urls as $url => $path) {
			if( StaticPublisher::echo_progress() ) {
				echo ' * Publishing page ' . ++$pagesProcessed . '/' . $totalURLs . ': ' . $url . PHP_EOL ;
				flush();
			}
			$this->generateAndSaveStaticCacheFile( $url, $path );
			
		}
	}
	
	/**
     * Does the meat of fetching the page with Director::test() and also saves
     * the fetched content to disk.
	 *
	 * @param string $url
	 * @param string $path 
	 */
	protected function generateAndSaveStaticCacheFile( $url, $path ) {
		
		$url = $this->URLSanitation( $url );
		
		if( $url === false ) {
			continue;
		}

		Requirements::clear();
		$response = Director::test( str_replace( '+', ' ', $url ) );
		Requirements::clear();

		singleton( 'DataObject' )->flushCache();

		$content = $this->getContent( $this->fileExtension, $response );

		if( self::$static_base_url ) {
			Director::setBaseURL($currentBaseURL); 
		}

		if( $this->fileExtension == 'php' ) {
			SSViewer::setOption( 'rewriteHashlinks', true );
		}

		$this->writeContentToCacheFile( $this->destFolder, dirname($path), basename($path), $content );
	}
	
	/**
	 * Generate file content			
	 * PHP file caching will generate a simple script from a template
	 *
	 * @param string $fileExtension
	 * @param SS_HTTPResponse||string $response
	 * @return string 
	 */
	protected function getContent( $fileExtension, $response ) {
		if($this->fileExtension == 'php') {
			return $this->getContentForPHPCacheFile( $response );
		} else {
			return $this->getContentForHTMLCacheFile( $response );
		} 
	}
	
	/**
	 * Returns content meant for putting in a dynamic PHP cache file
	 *
	 * @param SS_HTTPResponse||string $response
	 * @return string 
	 */
	protected function getContentForPHPCacheFile( $response ) {
		if( !is_object( $response ) ) {
			return $this->generatePHPCacheFile( $response . '', HTTP::get_cache_age(), date( 'Y-m-d H:i:s' ) );
		}
		if( in_array( $response->getStatusCode(), array( 301, 302 ) ) ) {
			return $this->generatePHPCacheRedirection( $response->getHeader( 'Location' ) );
		} else {
			return $this->generatePHPCacheFile( $response->getBody(), HTTP::get_cache_age(), date( 'Y-m-d H:i:s' ), $response->getHeader('Content-Type') );
		}
	}
	
	/**
	 * Returns content meant for putting in a non-dynamic cache file
	 *
	 * @param SS_HTTPResponse||string $response
	 * @return string 
	 */
	protected function getContentForHTMLCacheFile( $response ) {
		if( !is_object( $response ) ) {
			return $response . '';
		}
		
		if( in_array( $response->getStatusCode(), array( 301, 302 ) ) ) {
			$absoluteURL = Director::absoluteURL( $response->getHeader( 'Location' ) );
			return "<meta http-equiv=\"refresh\" content=\"2; URL=$absoluteURL\">";
		} else {
			return $response->getBody();
		}
	}

	/**
	 * Writes a file to the disk with content in an atomic behaviour
	 * 
	 * @param string $cacheFolder - the cachefolder, e.g: 'cache'
	 * @param string $folder - the folder inside the $cacheFolder
	 * @param type $filename - the cachefile's name
	 * @param type $content - the content of the file
	 * @return void
	 */
	protected function writeContentToCacheFile( $cacheFolder, $folder, $filename,  $content = '' ) {
		
		if( $folder == '.' ) {
			$folder = '';
		}
        
        if( $folder ) {
            $folder .= '/';
        }
		
		$fullfilename = BASE_PATH . '/' . $cacheFolder . '/' . $folder . $filename;
		
		Filesystem::makeFolder( dirname( $fullfilename ) );
		if( !$content ) {
			return;
		}
		
		$fh = fopen( $fullfilename , "w" );
		
		if( flock( $fh, LOCK_EX ) ) {
			fwrite( $fh, $content );
			flock( $fh, LOCK_UN );
		} else {
			if( StaticPublisher::echo_progress() ) {
				echo PHP_EOL.' *** Couldn\'t get an exclusive file lock on ' . $fullfilename . PHP_EOL . PHP_EOL;
			}
		}
		
		fclose( $fh );
	}
	
	/**
	 * Sanitizes the url so that a Director::test can understand it
	 *
	 * @param string $url
	 * @return false || string
	 */
	protected function URLSanitation( $url ) {
		if( $url == "" ) {
			$url = "/";
		}
        
		if( $url && !is_string( $url ) ) {
			if( StaticPublisher::echo_progress() ) {
				echo " * Bad url: " . var_export( $url, true ) . PHP_EOL . '    - Skipping it'.PHP_EOL;
			}
			return false;
		}

		if( Director::is_relative_url( $url ) ) {
			$url = Director::absoluteURL( $url );
		}
		return $url;
	}
	
	/**
	 * Generate the templated content for a PHP script that can serve up the given piece of content with the given age and expiry
	 */
	protected function generatePHPCacheFile($content, $age, $lastModified, $mimeType = 'text/html; charset="utf-8"' ) {
		$template = file_get_contents( BASE_PATH . '/cms/code/staticpublisher/CachedPHPPage.tmpl' );
		return str_replace(
			array( '**MAX_AGE**', '**LAST_MODIFIED**', '**CONTENT**', '**CONTENT_TYPE_HEADER**' ),
			array((int)$age, $lastModified, $content, $mimeType ),
			$template
		);
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