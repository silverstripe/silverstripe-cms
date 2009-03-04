/*
	This class is wrapper for SWFUpload class. 
	If you want use SWFUpload, please use this class becuase it will take care of configuration
	error handling and other things.

*/

Upload = Class.create();
Upload.prototype = {
	
	/**
	 * Sets configuration data provided from user if smth is missing sets default value.
	 *
	 * @param params object contains all configuration data for upload.
	*/
	initialize: function(params) {
		this.filesUploaded = 0;
		this.filesToUpload = 0;
		this.folderID = 'root';
		this.uploadInProgress = false;
		this.uploadMessage = '';
		if(typeof params.fileSizeLimit != 'undefined') this.setFileSizeLimit = params.fileSizeLimit; else this.fileSizeLimit = '30720';
		if(typeof params.fileTypes != 'undefined') this.fileTypes = params.fileTypes; else this.fileTypes = '*.*';
		if(typeof params.fileTypesDescription != 'undefined') this.fileTypesDescription = params.fileTypesDescription; else this.fileTypesDescription = 'All Files';
		if(typeof params.fileUploadLimit != 'undefined') this.fileUploadLimit = params.fileUploadLimit; else this.fileUploadLimit = '6';
		if(typeof params.beginUploadOnQueue != 'undefined') this.beginUploadOnQueue = params.beginUploadOnQueue; else this.beginUploadOnQueue = false;
		if(typeof params.fileQueued != 'undefined') this.fileQueued = params.fileQueued; 
		if(typeof params.fileProgress != 'undefined') this.fileProgress = params.fileProgress; else this.fileProgress = Prototype.emptyFunction;
		if(typeof params.fileCancelled != 'undefined') this.fileCancelled  = params.fileCancelled;
		if(typeof params.fileComplete != 'undefined') this.fileComplete = params.fileComplete ;
		if(typeof params.queueComplete != 'undefined') this.queueComplete = params.queueComplete;
		if(typeof params.buildUI != 'undefined') this.customBuildUI = params.buildUI;
		if(typeof params.securityID != 'undefined') this.securityID = params.securityID;
		this.onLoad();
	},
	
	/**
	 * Creates SWFUpload object for uploading files.  
	 * 
	*/
	onLoad: function() {
		path = this.getBasePath();
		sessId = this.getSessionId();//Because flash doesn't send proper cookies, we need to set session id in URL. 
		this.swfu = new SWFUpload({
				upload_url: path + 'admin/assets/UploadForm?SecurityID=' + this.securityID +  '&PHPSESSID=' + sessId,   // Relative to the SWF file
				file_post_name: 'Files',
				file_size_limit : this.fileSizeLimit,
				file_types : this.fileTypes,
				file_types_description : this.fileTypesDescription,
				file_upload_limit : this.fileUploadLimit,
				begin_upload_on_queue : this.beginUploadOnQueue,
				use_server_data_event : true,
				validate_files: false,

				file_queued_handler : this.uploadFileQueuedCallback.bind(this),
				upload_success_handler : this.uploadFileCompleteCallback.bind(this),
				upload_progress_handler: this.uploadFileProgressCallback.bind(this),
				error_handler : this.uploadErrorCallback.bind(this),
				file_validation_handler : Prototype.emptyFunction,
				file_cancelled_handler: Prototype.emptyFunction,
				
				flash_url : 'jsparty/SWFUpload/swfupload_f9.swf',	// Relative to this file
				swfupload_loaded_handler: this.buildUI.bind(this),
				debug: false
			});
	},
	
	/**
	 * Retrieves base path from URL.
	 * TODO: Use base tag. 
	*/
	
	getBasePath: function() {
		var path = 'http://' + window.location.host + window.location.pathname;

		if(path.match(/^(.*\/)admin/i)) return RegExp.$1;
		else return path;
	},
	
	/**
	 * Retrieves sessionId from cookie. 
	 * 
	*/

	getSessionId: function() {
		var start = document.cookie.indexOf('PHPSESSID')+10;
		var end = document.cookie.indexOf(';',start);
		if(end == -1) end = document.cookie.length;
		return document.cookie.substring(start,end);
	},
	
	/**
	 * Calls method defined by user, method should create user interface. 
	 * 
	*/
	
	buildUI: function() {
		this.customBuildUI();			
	},
	
	/**
	 * Called when new file is added to the queue
	 * 
	 * @param file object 
	 * @param queueLength int
	*/
	
	uploadFileQueuedCallback: function(file,queueLength) {
		this.filesToUpload++;
		this.fileQueued(file, queueLength);
		this.addFileParam(file);
	},
	
	/**
	 * Called when uploading of particular file has finished
	 * 
	 * @param file object 
	 * @param servedData string
	*/
	uploadFileCompleteCallback: function(file,serverData) {
		this.filesUploaded++;
		if(serverData) {
		   var toEval = serverData.substr(serverData.indexOf('<script'));
		   toEval = toEval.replace('<script type="text/javascript">','');
		   toEval = toEval.replace('</script>','');
		   this.uploadMessage = toEval;
	   }

		this.fileComplete(file, serverData);
		
		// Run the next file in the queue, if there is one
		if(this.swfu.getStats().files_queued > 0) this.startUpload();
		// Otherwise indicate that the queue is finished
		else {
			this.queueComplete();
			this.uploadInProgress = false;
			this.filesUploaded = 0;
			this.filesToUpload = 0;
		}
	},
	
	/**
	 * Called during uploading file. 
	 *
	 * @param file object 
	 * @param bytes_complete int
	*/
	
	uploadFileProgressCallback: function(file, bytes_complete) {
		this.uploadInProgress = true;
		this.fileProgress(file, bytes_complete);
	},
		
	/**
	 * Called on error.
	 * @param error_code int
	 * @param file object
	 * @param message string
	*/

	uploadErrorCallback: function(error_code, file, message) {
		this.swfu.cancelQueue();
		switch(error_code) {
			case SWFUpload.ERROR_CODE_HTTP_ERROR:
				alert('You have encountered an error. File hasn\'t been uploaded. Please hit the "Refresh" button in your web browser. Error Code: HTTP Error, File name: ' + file.name + ', Message: ' + msg);
			break;
			case SWFUpload.ERROR_CODE_IO_ERROR:
				alert('You have encountered an error. File hasn\'t been uploaded. Please hit the "Refresh" button in your web browser. Error Code: IO Error, File name: ' + file.name + ', Message: ' + msg);
			break;
			case SWFUpload.ERROR_CODE_SECURITY_ERROR:
				alert('You have encountered an error. File hasn\'t been uploaded. Please hit the "Refresh" button in your web browser. Error Code: Security Error, File name: ' + file.name + ', Message: ' + msg);
			break;
			case SWFUpload.ERROR_CODE_FILE_EXCEEDS_SIZE_LIMIT:
				alert('Files cannot be bigger than ' + this.fileSizeLimit/1024 + ' MB.');
			break;
			case SWFUpload.ERROR_CODE_ZERO_BYTE_FILE:
				alert('Files cannot be empty');
			break;
			case SWFUpload.ERROR_CODE_QUEUE_LIMIT_EXCEEDED:
				alert('You can only have six files in queue');
			break;
			case SWFUpload.ERROR_CODE_UPLOAD_FAILED:
				alert('You have encountered an error. File hasn\'t has been uploaded. Please hit the "Refresh" button in your web browser');
			break;
			case SWFUpload.ERROR_CODE_SPECIFIED_FILE_NOT_FOUND:
				alert('You have encountered an error. File hasn\'t has been uploaded. Please hit the "Refresh" button in your web browser');
			break;
			default:
				alert('You have encountered an error. File hasn\'t has been uploaded. Please hit the "Refresh" button in your web browser');
		}
	},
	
	/**
	 * Because we are on top of standard upload we need to add some POST vars that 
	 * normally are being sent as part of form.
	 *
	 * @param file object
	*/
	 
	addFileParam: function(file) {
		this.swfu.addFileParam(file.id,'ID',this.folderID);
		this.swfu.addFileParam(file.id,'action_doUpload','1');
		this.swfu.addFileParam(file.id,'Files',file.name);
		this.swfu.addFileParam(file.id,'MAX_FILE_SIZE','31457280');
	},
	
	/**
	 * Starts file explorer. 
	 *
	*/
	
	browse: function() {
		this.swfu.selectFiles();
	},
	
	/**
	 *  Starts upload
	 * 
	*/
	
	startUpload: function() {
		this.swfu.startUpload();
	},
	
	/**
	 * Cancels uploading of file. 
	*/
	
	cancelUpload: function(fileId) {
		this.filesToUpload--;
		this.swfu.cancelUpload(fileId);
	},
	
	/*
	 * Getters and setters.
	*/   
	setFolderID: function(id) {
		this.folderID = id;
	},
	
	getFilesUploaded: function() {
		return this.filesUploaded;
	},
	
	getFilesToUpload: function() {
		return this.filesToUpload;
	},
	
	getUploadMessage: function() {
		return this.uploadMessage;
	},
	
	isUploadInProgress: function() {
		return this.uploadInProgress;
	}
}
