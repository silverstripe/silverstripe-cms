<?php

	class ImageEditor extends Controller {
		
		public $fileToEdit = "";
		
		public function __construct() {
		}
		
		public function index() {
			Requirements::clear();
			Requirements::javascript("jsparty/prototype.js");
			Requirements::javascript("jsparty/scriptaculous/scriptaculous.js");
			Requirements::javascript("/cms/javascript/ImageEditor/Require.js");
			Requirements::javascript("cms/javascript/ImageEditor/ImageEditor.js");
			Requirements::css("cms/css/ImageEditor/ImageEditor.css");
			
			if(!isset($this->requestParams['fileToEdit'])) $this->raiseError();
			$fileWithPath = $this->requestParams['fileToEdit'];
			$this->fileToEdit = $this->file2Origin($fileWithPath);
					
			return $this->renderWith(__CLASS__);
		}
		
		private function raiseError() 
		{
			Debug::friendlyError(500,"Bad arguments",__FILE__,__LINE__,'');
			exit();	
		}
		
		public function manipulate() {
			$fileName = $this->requestParams['file'];
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
			$this->returnImage($gd,'/assets/tmp/' . $rand . '.jpg');	
		}
		
		public function save() {
			if(isset($this->requestParams['originalFile']) && isset($this->requestParams['editedFile'])) {
				$originalFile = $this->requestParams['originalFile'];
				$editedFile = $this->requestParams['editedFile'];
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
		}
		
		private function returnImage(GD $gd,$strFile)
		{
			list($width, $height) = getimagesize('..' . $strFile);		
			echo json_encode(array(
								'fileName' => $strFile,
								'width' => $width,
								'height' => $height)
							);
		}
		
		private function file2Origin($file) {
			$file = str_replace('_resampled/','',$file);
			$file = str_replace('_resampled/','',$file);
			$file = str_replace('AssetLibraryPreview-','',$file);
			$this->checkFileExists($file);
			return $file;
		}

		private function url2File($url) {
			return '..' . substr($url,strpos($url,'/assets'));
		}
		
		
		private function checkFileExists($url) {
			$pathInfo = pathinfo($url);
			if(count($pathInfo) <= 3) $this->raiseError();
			if(!in_array($pathInfo['extension'],array('jpeg','jpg','jpe','png','gif'))) $this->raiseError();
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
				if(file_exists($realPath)) {					return true;
				} else {
					$this->raiseError();
				}
			} else {
				$this->raiseError();
			}		
		}
	}
?>