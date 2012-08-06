<?php
/**
 * Fetch translation files as a ZIP from getlocalization.com,
 * extract the YAML files from it, and do some reformatting on them
 * to make them suitable for usage in the SilverStripe translation system.
 * 
 * Requires 'unzip' binary.
 */
class UpdateTranslationsTask extends SilverStripeBuildTask {

	static $url_translations = 'https://www.getlocalization.com/%s/api/translations/zip/';

	/**
	 * Absolute path to module base (not lang folder)
	 */
	protected $modulePath;
	
	/**
	 * GetLocalization product name, as documented in their API:
	 * http://www.getlocalization.com/library/api/get-localization-file-management-api/
	 * @var [type]
	 */
	protected $glProductName;

	protected $glUser;

	protected $glPassword;

	protected $langFolders = array(
		'yml' => 'lang',
		'js' => 'javascript/lang'
	);

	/**
	 * @var String If set, will use existing files rather than try to download them.
	 */
	protected $downloadPath;

	public function getGlUser() {
	    return $this->glUser;
	}
	
	public function setGlUser($newGlUser) {
	    $this->glUser = $newGlUser;
	    return $this;
	}

	public function getGlPassword() {
	    return $this->glPassword;
	}
	
	public function setGlPassword($newGlPassword) {
	    $this->glPassword = $newGlPassword;
	    return $this;
	}

	public function setModulePath($path) {
		$this->modulePath = $path;
	}

	public function setDownloadPath($path) {
		$this->downloadPath = $path;
	}

	public function setGlProductName($name) {
		$this->glProductName = $name;
	}

	public function main() {
		if (!is_dir($this->modulePath)) {
			throw new BuildException("Invalid target directory: $this->modulePath");
		}

		$downloadPath = $this->downloadPath ? $this->downloadPath : $this->download();
		$files = $this->findFiles($downloadPath);
		foreach($files as $file) {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if($ext == 'yml') {
				$this->processYmlFile($file);	
			} elseif($ext == 'js') {
				$this->processJavascriptFile($file);	
			} else {
				throw new LogicException(sprintf('Unknown extension: %s', $ext));
			}
		}
		
	}

	/**
	 * @return File path to a folder structure containing translation files
	 */
	protected function download() {
		$tmpFolder = tempnam(sys_get_temp_dir(), $this->glProductName . '-');
		$tmpFilePath = $tmpFolder . '.zip';
		rename($tmpFolder, $tmpFilePath);
		$url = sprintf(self::$url_translations, $this->glProductName);

		$this->log(sprintf("Downloading $url to $tmpFilePath"));

		$ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $this->glUser. ":" . $this->glPassword);  
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($code >= 400) {
    	throw new BuildException(sprintf(
    		'Error downloading %s: %s %s', 
    		$url, 
    		$code,
    		$data
    	));
    }
    if(curl_error($ch)) {
    	throw new BuildException(sprintf(
    		'Error downloading %s: %s (#%s)', 
    		$url, 
    		curl_error($ch),
    		curl_errno($ch)
    	));
    }

    curl_close($ch);
    file_put_contents($tmpFilePath, $data);

    $this->log(sprintf("Extracting to $tmpFolder"));
    $this->exec("unzip $tmpFilePath -d $tmpFolder");

    return $tmpFolder;
	}

	/**
	 * @param String Absolute path to a folder structure containing translation files
	 * @return Array with file paths
	 */
	protected function findFiles($path) {
		// Recursively find files with certain extensions.
    // Can't use glob() since its non-recursive.
    // Directory structure doesn't matter here.
    $files = array();
    $matches = new RegexIterator(
			new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path)
			), 
			'/^.+\.(yml$|js)/i', 
			RecursiveRegexIterator::GET_MATCH
		);
		foreach($matches as $match) $files[] = $match[0];
		return $files;
	}

	protected function processYmlFile($file) {
		$this->log(sprintf("Processing $file"));

		// Rename locale to correct convention (underscored rather than dashed).
		// GL wants filenames to adhere to its saved locales, but SS framework
		// can't easily change to that format for backwards compat reasons, so we need to convert.
		// The passed in file name doesn't really matter here, only the contained locale.
		// By convention, the first line in the YAML file is always the locale used, as a YAML "root key".
		$localeRegex = '/^([\w-_]*):/';
		$content = file_get_contents($file);
		preg_match($localeRegex, $content, $matches);
		$locale = $matches[1];
		$locale = str_replace('-', '_', $locale);
		$locale = str_replace(':', '', $locale);
		$content = preg_replace($localeRegex, $locale . ':', $content);
		
		// Convert faulty multiline double quoted string YAML
		// to block format, in order to allow the YAML Parser to open it later
		// TODO Remove once getlocalization.com has fixed their output format (see support.getlocalization.com #2022)
		$isBlock = false;
		$blockIndex = -1;
		$lines = explode(PHP_EOL, $content);
		$keyedLineRegex = '/^\s*[\w\d-_\.]*:\s*/'; 
		$leadingQuoteRegex = '/^\s*\"/';
		$trailingQuoteRegex = '/[^\\\\]\"$/';
		foreach($lines as $i => $line) {
			preg_match($keyedLineRegex, $line, $matches);
			$key = $matches ? $matches[0] : null;
			$val = trim(preg_replace($keyedLineRegex, '', $line));
			// If its a multiline double quoted string (no unescaped closing quote)
			if($val && $line != '"' && preg_match($leadingQuoteRegex, $val) && !preg_match($trailingQuoteRegex, $val)) {
				$isBlock = true;
				$blockIndex = $i;
			} elseif($key) {
				$isBlock = false;
				$blockIndex = -1;
			} else {
				$lines[$blockIndex] .= $line;
				unset($lines[$i]);
			}
		}
		$content = implode(PHP_EOL, $lines);

		// Parse YML as a sanity check,
		// and reorder alphabetically by key to ensure consistent diffs.
		require_once dirname(__FILE__) . '/../framework/thirdparty/zend_translate_railsyaml/library/Translate/Adapter/thirdparty/sfYaml/lib/sfYaml.php';
		require_once dirname(__FILE__) . '/../framework/thirdparty/zend_translate_railsyaml/library/Translate/Adapter/thirdparty/sfYaml/lib/sfYamlParser.php';
		require_once dirname(__FILE__) . '/../framework/thirdparty/zend_translate_railsyaml/library/Translate/Adapter/thirdparty/sfYaml/lib/sfYamlDumper.php';
		$yamlHandler = new sfYaml();
		$yml = $yamlHandler->load($content);
		if(isset($yml[$locale]) && is_array($yml[$locale])) {
			ksort($yml[$locale]);
			foreach($yml[$locale] as $k => &$v) {
				if(is_array($v)) ksort($v);
			}
		}
		$content = $yamlHandler->dump($yml, 99); // don't inline first levels

		// Save into correct path, overwriting existing files
		$path = $this->modulePath . '/' . $this->langFolders['yml'] . '/' . $locale . '.yml';
		$this->log("Saving to $path");
		file_put_contents($path, $content);
	}

	protected function processJavascriptFile($file) {
		$this->log(sprintf("Processing $file"));

		$locale = pathinfo($file, PATHINFO_FILENAME);
		$locale = str_replace('-', '_', $locale);

		// Save into correct path, overwriting existing files
		$path = $this->modulePath . '/' . $this->langFolders['js'] . '/' . $locale . '.yml';
		$this->log("Saving to $path");
		file_put_contents($path, file_get_contents($file));
	}
}


?>