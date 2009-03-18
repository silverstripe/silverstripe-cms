<?php
/**
 * This Controller handles all operation needed for ImageEditor to work(expect for GD operations).
 * @package cms
 * @subpackage assets
 */
class ImageEditor extends Controller {
	
	static $allowed_actions = array(
		'*' => 'CMS_ACCESS_CMSMain'
	);
	
	public $fileToEdit = "";
	
	public $fileToEditOnlyName = "";
	
	/**
	 * Includes all JS required for ImageEditor. This method requires setting
	 * a fileToEdit URL in POST.
	 *
	 * @return String
	*/ 
	public function index() {
		Requirements::clear();
		Requirements::javascript(THIRDPARTY_DIR . '/prototype.js');
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/scriptaculous.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/Utils.js');
		//Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/ImageHistory.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/Image.js');
		//Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/ImageTransformation.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/Resizeable.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/Effects.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/Environment.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/Crop.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/Resize.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/ImageBox.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/ImageEditor.js');
		Requirements::javascript(CMS_DIR . '/javascript/ImageEditor/DocumentBody.js');

		Requirements::javascript(THIRDPARTY_DIR . '/loader.js');
		Requirements::javascript(THIRDPARTY_DIR . '/behaviour.js');
		Requirements::javascript(CMS_DIR . '/javascript/LeftAndMain.js');
		Requirements::css(CMS_DIR . 'css/ImageEditor/ImageEditor.css');

		if(!isset($this->requestParams['fileToEdit'])) $this->raiseError();
		$fileWithPath = $this->requestParams['fileToEdit'];
		$this->fileToEdit = $this->file2Origin($fileWithPath);
		$this->fileToEditOnlyName = $this->urlToFilename($this->fileToEdit);  
		return $this->renderWith(__CLASS__);
	}
	
	/**
	 * Method is used for manipulating photos.
	 * Method requires two params set in POST
	 * 	file - file on which operation will be performed
	 *  command - name of operation(crop|rotate|resize)
	 * 
	 * Each operation requires additional parameters.
	 *
	 * @return String - JSON array with image properties (width,height,url).
	*/ 
	public function manipulate() {
		$fileName = $this->requestParams['file'];
		if(strpos($fileName,'?') !== false) $fileName = substr($fileName,0,strpos($fileName,'?'));
		$command = $this->requestParams['command'];
		$this->checkFileExists($fileName);
		$fileInfo = pathinfo($fileName);
		$gd = new GD($this->url2File($fileName));
		switch($command) {
			case 'rotate':
				$gd = $gd->rotate(90);
			break;
			case 'resize':
				$imageNewWidth = $_POST['newImageWidth'];
				$imageNewHeight = $_POST['newImageHeight'];
				$gd = $gd->resize($imageNewWidth,$imageNewHeight);	
			break;
			case 'crop':
				$top = $_POST['top'];
				$left = $_POST['left'];
				$width = $_POST['width'];
				$height = $_POST['height'];
				$gd = $gd->crop($top,$left,$width,$height);
			break;
  			case 'greyscale':
				$gd = $gd->greyscale();                 
				break;
            case 'sepia':
				$gd = $gd->sepia();                 
				break;
			case 'blur':
				$gd = $gd->blur();                 
				break;
			case 'adjust-contrast':
				$value = intval($_POST['value']);
				$gd = $gd->contrast($value);
				break;
			case 'adjust-brightness':
				$value = intval($_POST['value']);
				$gd = $gd->brightness($value);
				break;
			case 'adjust-gamma':
				$value = floatval($_POST['value']);
				$gd = $gd->gamma($value);
				break;
		}
		$rand = md5(rand(1,100000));
		$gd->writeTo(ASSETS_PATH . '/_tmp/' . $rand . '.' . $fileInfo['extension']);
		return $this->getImageInfoInJSON($gd,ASSETS_PATH . '/_tmp/' . $rand . '.' . $fileInfo['extension']);	
	}
	
	/**
	 * Method is used for saving photos.
	 * Method requires two params set in POST
	 * 	originalFile - this file will be replaced by second file
	 *  editedFile - this file will replace first file.
	 * 
	 * After replacing original file all thumbnails created from it are removed.
	 *
	 * @return String - Message that everything went ok.
	*/ 
	
	public function save() {
		if(isset($this->requestParams['originalFile']) && isset($this->requestParams['editedFile'])) {
			$originalFile = $this->requestParams['originalFile'];
			$editedFile = $this->requestParams['editedFile'];
			if(strpos($originalFile,'?') !== false) $originalFile = substr($originalFile,0,strpos($originalFile,'?'));
			if($this->checkFileExists($originalFile) && $this->checkFileExists($editedFile)) {
				if($editedFile != $originalFile && copy($this->url2File($editedFile),$this->url2File($originalFile))) {
					$image = DataObject::get_one('File','Filename = \'' . substr($this->url2File($originalFile),3) . '\'');
                       $image->deleteFormattedImages();
					$image->generateFormattedImage('AssetLibraryPreview');
				} else {
					$this->raiseError();
				}					
			} else {
				$this->raiseError();
			}
		} else {
			$this->raiseError();
		}
		return 'parent.parent.parent.statusMessage(\'Image saved\',\'good\',false);';
	}
	
	/**
	 * Method is invoked when ImageEditor is closed whether image is saved or not.
	 * 
	 * /assets/tmp is folder where we store temporary images created during editing so 
	 * after closing they are no necessity to keep them.
	 * 
	 * @return null
	*/ 
	
	public function close() {
		$tmpDir = ASSETS_PATH . '/_tmp';
		if(file_exists($tmpDir)) {
		    Filesystem::removeFolder($tmpDir);
		    mkdir($tmpDir, Filesystem::$folder_create_mask);
		}
	}
	
	/**
	 * Method return JSON array containing info about image.
	 * 
	 * @param gd - GD object used for retrieving info about image
	 * @param file 
	 * 
	 * @return string JSON array explained in manipulate method comment
	*/ 
	
	private function getImageInfoInJSON(GD $gd,$file) {
		return '{"fileName":"' . $file . '","width":' . $gd->getWidth() . ',"height":' . $gd->getHeight() . '}';
	}
	
	/**
	 * Method converts thumbnail file name to file name of it's "parent"
	 * 
	 * @param file - name of thumbnail file
	 * 
	 * @return string name of parent file.
	*/ 
	
	private function file2Origin($file) {
		$file = str_replace('_resampled/','',$file);
		$file = str_replace('_resampled/','',$file);
		$file = str_replace('AssetLibraryPreview-','',$file);
		$this->checkFileExists($file);
		return $file;
	}
	/**
	 * Method converts URL of file to file path in file system.
	 * 
	 * @param url - url of file
	 * 
	 * @return string path of file in file system
	*/ 
	
	private function url2File($url) {
		return '..' . substr($url,strpos($url,'/assets'));
	}
	
	/**
	 * Method checks if file exists and have proper name and extension.
	 * 
	 * If any of constraints aren't fulfilled method will generate error.
	 * 
	 * @param url - url of file
	 * 
	 * @return boolean 
	*/ 
	
	private function checkFileExists($url) {
		if(strpos($url,'?') !== false) $url = substr($url,0,strpos($url,'?'));
		$pathInfo = pathinfo($url);
		if(count($pathInfo) < 3) $this->raiseError();
		if(!in_array($pathInfo['extension'],array('jpeg','jpg','jpe','png','gif','JPEG','JPG','JPE','PNG','GIF'))) $this->raiseError();
		$path = explode('/',$pathInfo['dirname']);
		if(count($path) > 1) {
			$assetId = array_search('assets',$path);
			if($assetId > 0) {
				$realPath = '../' . implode('/',array_slice($path,$assetId,count($path) - $assetId));
				if(strpos($pathInfo['basename'],'AssetLibraryPreview') !== false) {
					$realPath .= '/' . substr($pathInfo['basename'],strpos($pathInfo['basename'],'-'));
				} else {
					$realPath .= '/' . $pathInfo['basename']; 
				}
			} else {
				$this->raiseError();
			}
			if(file_exists($realPath)) {
				return true;
			} else {
				$this->raiseError();
			}
		} else {
			$this->raiseError();
		}		
	}
	
	/**
	 * Method raiser error. Error is showed using statusMessage function.
	 * 
	 * @param message - error message
	 * 
	*/ 
	
	private function raiseError($message = "") {
		echo "parent.parent.parent.statusMessage('Error: " . $message . "','bad',false);";
		exit();	
	}
	
	/**
        * Method converts retrieves filename from url
        *
        * @param url
        * 
       */ 
	
	private function urlToFilename($url) {
	    $path = pathinfo($url);
	    return $path['filename'] . "." . substr($path['extension'],0,strpos($path['extension'],'?'));  	
	}
}

?>