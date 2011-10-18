<?php
/* 
 * 
All code covered by the BSD license located at http://silverstripe.org/bsd-license/
 */

/**
 * Build task that provides some commonly used functionality
 *
 * @author marcus
 */
abstract class SilverStripeBuildTask extends Task {
	
	/**
	 * @var boolean
	 */
	protected $verbose = false;

	protected $cleanupEnv = false;
	
	protected function configureEnvFile() {
		// fake the _ss_environment.php file for the moment
		$ssEnv = <<<TEXT
<?php
// Set the \$_FILE_MAPPING for running the test cases, it's basically a fake but useful
global \$_FILE_TO_URL_MAPPING;
\$_FILE_TO_URL_MAPPING[dirname(__FILE__)] = 'http://localhost';
TEXT;

		$envFile = dirname(dirname(__FILE__)).'/_ss_environment.php';
		$this->cleanupEnv = false;
		if (!file_exists($envFile)) {
			file_put_contents($envFile, $ssEnv);
			$this->cleanupEnv = true;
		}
	}

	function cleanEnv() {
		if ($this->cleanupEnv) {
			$envFile = dirname(dirname(__FILE__)).'/_ss_environment.php';
			if (file_exists($envFile)) {
				unlink($envFile);
			}
		}
	}

	function devBuild() {
		if (file_exists('sapphire/cli-script.php')) {
			$this->log("Running dev/build");
			$this->exec('php sapphire/cli-script.php dev/build');
		}
	}
	
	
	/**
	 * Get some input from the user
	 *
	 * @param string $prompt
	 * @return string
	 */
	function getInput($prompt) {
		require_once 'phing/input/InputRequest.php';
		$request = new InputRequest($prompt);
        $request->setPromptChar(':');
        
        $this->project->getInputHandler()->handleInput($request);
        $value = $request->getInput();
		return $value;
	}

	function exec($cmd, $returnContent = false, $ignoreError = false) {
		$ret = null;
		$return = null;
		
		if($this->verbose) $this->log($cmd);
		
		if ($returnContent) {
			$ret = shell_exec($cmd);
		} else {
			passthru($cmd, $return);
		}
		
		if ($return != 0 && !$ignoreError) {
			throw new BuildException("Command '$cmd' failed");
		}
		
		return $ret;
	}
}