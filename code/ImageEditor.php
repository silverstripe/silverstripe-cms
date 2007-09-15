<?php
/**
 * This Controller handles all operation needed for ImageEditor to work(expect for GD operations).
 * 
*/

	class ImageEditor extends Controller {
		
		public $fileToEdit = "";
		
		/**
		 * Includes all JS required for ImageEditor. This method requires setting
		 * a fileToEdit URL in POST.
		 *
		 * @return String
		*/ 
		public function index() {
			Requirements::clear();
			Requirements::javascript("jsparty/prototype.js");
			Requirements::javascript("jsparty/scriptaculous/scriptaculous.js");
            Requirements::javascript('cms/javascript/ImageEditor/Utils.js');
            Requirements::javascript('cms/javascript/ImageEditor/ImageHistory.js');
            Requirements::javascript('cms/javascript/ImageEditor/Image.js');
            Requirements::javascript('cms/javascript/ImageEditor/ImageTransformation.js');
            Requirements::javascript('cms/javascript/ImageEditor/Resizeable.js');
            Requirements::javascript('cms/javascript/ImageEditor/Effects.js');
            Requirements::javascript('cms/javascript/ImageEditor/Environment.js');
            Requirements::javascript('cms/javascript/ImageEditor/Crop.js');
            Requirements::javascript('cms/javascript/ImageEditor/Resize.js');
            Requirements::javascript('cms/javascript/ImageEditor/ImageBox.js');
			Requirements::javascript("cms/javascript/ImageEditor/ImageEditor.js");

			Requirements::javascript("jsparty/loader.js");
			Requirements::javascript("jsparty/behaviour.js");
			Requirements::javascript("cms/javascript/LeftAndMain.js");
			Requirements::css("cms/css/ImageEditor/ImageEditor.css");

			if(!isset($this->requestParams['fileToEdit'])) $this->raiseError();
			$fileWithPath = $this->requestParams['fileToEdit'];
			$this->fileToEdit = $this->file2Origin($fileWithPath);
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
			$gd = new GD($fileName);
			switch($command) {
				case 'rotate':
					$angle = $_POST['angle'];
					$gd = $gd->rotate($angle);
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
			}
			$rand = md5(rand(1,100000));
			$gd->writeTo('../assets/tmp/' . $rand . '.jpg');
			return $this->getImageInfoInJSON($gd,'assets/tmp/' . $rand . '.jpg');	
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
						$image = DataObject::get_one('File',"Filename = '" . substr($this->url2File($originalFile),3) . "'");
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
			return "parent.parent.parent.statusMessage('Image saved','good',false);";
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
			Filesystem::removeFolder('../assets/tmp');
		}
		
		/**
		 * Method return JSON array containing info about image.
		 * 
		 * @param gd - GD object used for retrieving info about image
		 * @param file 
		 * 
		 * @return string JSON array explained in manipulate method comment
		*/ 
		
		private function getImageInfoInJSON(GD $gd,$file)
		{
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
		
		private function raiseError($message = "") 
		{
			echo "parent.parent.parent.statusMessage('Error: " . $message . "','bad',false);";
			exit();	
		}
	}
?>