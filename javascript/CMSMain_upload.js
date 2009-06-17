/**
 * This class is used for upload in asset part.
 * If one of methods is not commented look for comment in Upload.js
  
*/
CMSMain_upload = Class.create();
CMSMain_upload.prototype = {
	initialize: function() {
		// This is disabled until we get it working reliably
		return;
	
		// We require flash 9
		pv = getFlashPlayerVersion();
		if(pv.major < 9) return;
	
		// Due to a bug in the flash plugin on Linux and Mac, we need at least version 9.0.64 to use SWFUpload
		if(pv.major == 9 && pv.minor == 0 && pv.rev < 64) return;

		// If those 2 checks pass, we can provide upload capabilities to the user
		this.iframe = window.top.document.getElementById('AssetAdmin_upload');
		this.onLoad();
	},

	onLoad: function() {
		this.upload = new Upload({
				fileUploadLimit : '6',
				securityID : $('SecurityID').value,
				beginUploadOnQueue : false,
				fileQueued : this.uploadFileQueuedCallback.bind(this),
				fileProgress : this.uploadProgressCallback.bind(this),
				fileCancelled : this.uploadFileCancelCallback.bind(this),
				fileComplete : this.uploadFileCompleteCallback.bind(this),
				queueComplete : this.uploadQueueCompleteCallback.bind(this),
				buildUI : this.extendForm.bind(this)
			});
	},
	
	/**
	 * Builds UI, called only when Upload object will be able to create flash uploader. 
	*/
	
	extendForm: function() {
		if(this.iframe.contentDocument == undefined) this.iframe.contentDocument = document.frames[0].document;//IE HACK   
		element = this.iframe.contentDocument.getElementById('Form_UploadForm');
		inputFile = this.iframe.contentDocument.getElementById('Form_UploadForm_Files-0');
		inputFileParent = inputFile.parentNode;
		inputFileParent.removeChild(inputFile);
		inputFile = this.iframe.contentDocument.createElement('input');
		inputFile.type = 'text';
		inputFile.id = 'Form_UploadForm_Files-0';
		inputFileParent.appendChild(inputFile);
		inputButton = this.iframe.contentDocument.getElementById('Form_UploadForm_Files-1');
		if(inputButton != null) inputButton.parentNode.removeChild(inputButton);
		inputButton = this.iframe.contentDocument.createElement('input');
		inputButton.type = 'button';
		inputButton.id = 'Form_UploadForm_Files-1';
		inputButton.value = ' Browse...';
		inputButton.style.width = '66px';
		inputButton.style.height = '19px';
		inputButton.style.position = 'relative';
		inputButton.style.top = '1px';
		inputButton.style.fontFamily = 'Arial';
		inputButton.style.fontSize = '1.06em';
		inputFileParent.appendChild(inputButton);
		Event.observe(inputButton,'click',this.onBrowseClick.bind(this));
		Event.observe(this.iframe.contentDocument.getElementById('Form_UploadForm_action_upload'),'click',this.onUploadClick.bind(this));
	},
	
	onBrowseClick: function(event) {
		this.upload.browse();
	},
	
	onUploadClick: function(event) {
		Event.stop(event);
		this.upload.startUpload();
	},
	
	uploadFileQueuedCallback: function(file,queueLength) {
		this.upload.setFolderID(this.iframe.contentDocument.getElementById('Form_UploadForm_ID').value); 
		this.iframe.contentDocument.getElementById('Form_UploadForm_action_upload').disabled = false;
		var fileContainer = this.iframe.contentDocument.getElementById('Form_UploadForm_FilesList');
		if(fileContainer == null) {
		   fileContainer = this.iframe.contentDocument.createElement('div');
		   fileContainer.id = 'Form_UploadForm_FilesList';
		   this.iframe.contentDocument.getElementById('Form_UploadForm').appendChild(fileContainer);
		}
		
		var fileToUpload = this.iframe.contentDocument.createElement('div');
		fileToUpload.id = 'Form_UploadForm_FilesList_' + file.id;
		fileToUpload.style.marginBottom = '3px';
		fileContainer.appendChild(fileToUpload);
		
		var fileName = this.iframe.contentDocument.createElement('div');
		fileName.id = 'Form_UploadForm_FilesList_Name_' + file.id;
		fileName.style.position = 'relative';
		fileName.style.top = '-4px';
		fileName.style.display = 'inline';
		fileName.style.padding = '2px';
		fileName.innerHTML = file.name;
		fileName.style.height = Element.getDimensions(fileName).height + 1 + 'px';//IE hack
		fileToUpload.appendChild(fileName);
		
		var fileProgress = this.iframe.contentDocument.createElement('div');
		fileProgress.id = 'Form_UploadForm_FilesList_Progress_' + file.id;
		Position.clone(fileName,fileProgress);	   
		fileProgress.style.backgroundColor = 'black';
		fileProgress.style.display = 'inline';
		fileProgress.style.position = 'absolute';
		fileProgress.style.left = '5px';
		fileProgress.style.width = '0px';
		fileProgress.finished = false;		
		fileProgress.style.top = parseInt(fileProgress.style.top) + 6 + 'px';
		fileProgress.style.height = Element.getDimensions(fileName).height + 1 + 'px';		
		fileToUpload.appendChild(fileProgress);
		
		var fileDelete = this.iframe.contentDocument.createElement('input');
		fileDelete.id = file.id;
		fileDelete.type = 'button';
		fileDelete.value = 'Delete';
		Element.addClassName(fileDelete,'delete');
		fileToUpload.appendChild(fileDelete);
		Event.observe(fileDelete,'click',this.uploadFileCancelCallback.bind(this));
	},
	
	uploadProgressCallback: function(file,bytesLoaded) {
		fileName = this.iframe.contentDocument.getElementById('Form_UploadForm_FilesList_Name_' + file.id);
		fileName.style.border = 'solid 1px black';
		fileProgress = this.iframe.contentDocument.getElementById('Form_UploadForm_FilesList_Progress_' + file.id);
		fileProgress.style.opacity = 0.3;fileProgress.style.filter = 'alpha(opacity=30)';
		if(!fileProgress.cloned) {
			Position.clone(fileName,fileProgress);
			fileProgress.style.width = '0px';
			fileProgress.cloned = true;
		}
		fileProgress.style.width = (bytesLoaded / file.size) * Element.getDimensions(fileName).width - 1 + 'px';
	},
	
	uploadFileCompleteCallback: function(file,serverData) {
		this.iframe.contentDocument.getElementById('Form_UploadForm_FilesList_Progress_' + file.id).finished = true;
	},
	
	uploadFileCancelCallback: function(event) {
		element = Event.element(event);
		fileId = element.id;
		fileContainer = this.iframe.contentDocument.getElementById('Form_UploadForm_FilesList');
		elementToDelete = this.iframe.contentDocument.getElementById('Form_UploadForm_FilesList_' + fileId);
		elementToDelete.parentNode.removeChild(elementToDelete);
		filesToUpload = fileContainer.childNodes.length;
		if(filesToUpload > 0) {
			this.iframe.contentDocument.getElementById('Form_UploadForm_action_upload').disabled = false;
		} else {
			this.iframe.contentDocument.getElementById('Form_UploadForm_action_upload').disabled = true;
		}
		$A(fileContainer.childNodes).each(
			function(item) {
				$A(item.childNodes).each(
					function(item) {
						if(item.id.indexOf('Name') != -1) {
							fileName = item;
						}
						if(item.id.indexOf('Progress') != -1) {
							fileProgress = item;
						}
					});
				 Position.clone(fileName,fileProgress);
				 if(fileProgress.finished == false) fileProgress.style.width = '0px';
			}
		);
	},
	
	uploadQueueCompleteCallback: function() {
		eval(this.upload.getUploadMessage().replace(/Uploaded 1 files/g,'Uploaded ' + this.upload.getFilesUploaded() + ' files'));
	}
}
window.top.document.CMSMain_upload = CMSMain_upload;
