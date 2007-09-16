Upload = Class.create();
Upload.prototype = {
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
        this.onLoad();
    },
    
    onLoad: function() {
        path = this.getBasePath();
        sessId = this.getSessionId();
        this.swfu = new SWFUpload({
                upload_target_url: path + '/assets/index/root?executeForm=UploadForm&PHPSESSID=' + sessId,   // Relative to the SWF file
                file_post_name: 'Files',
                file_size_limit : this.fileSizeLimit,  // 30 MB
                file_types : this.fileTypes, // or you could use something like: '*.doc;*.wpd;*.pdf',
                file_types_description : this.fileTypesDescription,
                file_upload_limit : this.fileUploadLimit,
                begin_upload_on_queue : this.beginUploadOnQueue,
                use_server_data_event : true,
                validate_files: false,

                file_queued_handler : this.uploadFileQueuedCallback.bind(this),
                file_complete_handler : this.uploadFileCompleteCallback.bind(this),
                file_progress_handler: this.uploadFileProgressCallback.bind(this),
                queue_complete_handler : this.uploadQueueCompleteCallback.bind(this),
                error_handler : this.uploadErrorCallback.bind(this),
                file_validation_handler : Prototype.emptyFunction,
                file_cancelled_handler: Prototype.emptyFunction,
                
                ui_container_id: 'abc1',
                degraded_container_id: 'abc2', 
                
                
 
                flash_url : 'jsparty/SWFUpload/SWFUpload.swf',    // Relative to this file
                ui_function: this.buildUI.bind(this),
                debug: false
            });
    },
    
    getBasePath: function() {
        var path = 'http://' + window.location.host + window.location.pathname;
        if(path[path.length-1] == '/') path = path.substring(0,path.length-1);
        return path;
    },
    
    getSessionId: function() {
        var start = document.cookie.indexOf('PHPSESSID')+10;
        var end = document.cookie.indexOf(';',start);
        if(end == -1) end = document.cookie.length;
        return document.cookie.substring(start,end);
    },
    
    buildUI: function() {
        this.customBuildUI();	        
    },
    
    uploadFileQueuedCallback: function(file,queueLength) {
        this.filesToUpload++;
        this.fileQueued(file, queueLength);
        this.addFileParam(file);
    },
    
    uploadFileCompleteCallback: function(file,serverData) {
        this.filesUploaded++;
        var toEval = serverData.substr(serverData.indexOf('<script'));
        toEval = toEval.replace('<script type="text/javascript">','');
        toEval = toEval.replace('</script>','');
        this.uploadMessage = toEval;
        this.fileComplete(file, serverData);
    },
    
    uploadFileProgressCallback: function(file, bytes_complete) {
        this.uploadInProgress = true;
        this.fileProgress(file, bytes_complete);
    },
    
    uploadQueueCompleteCallback: function() {
        this.queueComplete();
        this.uploadInProgress = false;
        this.filesUploaded = 0;
        this.filesToUpload = 0;
    },
    
    uploadErrorCallback: function(error_code, file, message) {
        this.swfu.cancelQueue();
        switch(error_code) {
	        case SWFUpload.ERROR_CODE_HTTP_ERROR:
	            alert('You have encountered an error. File hasn\'t been uploaded. Please hit the "Refresh" button in your web browser');
	        break;
	        case SWFUpload.ERROR_CODE_IO_ERROR:
	            alert('You have encountered an error. File hasn\'t been uploaded. Please hit the "Refresh" button in your web browser');
            break;
	        case SWFUpload.ERROR_CODE_SECURITY_ERROR:
	            alert('You have encountered an error. File hasn\'t been uploaded. Please hit the "Refresh" button in your web browser');
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
     
    addFileParam: function(file) {
        this.swfu.addFileParam(file.id,'ID',this.folderID);
        this.swfu.addFileParam(file.id,'action_doUpload','1');
        this.swfu.addFileParam(file.id,'Files',file.name);
        this.swfu.addFileParam(file.id,'MAX_FILE_SIZE','31457280');
    },
    
    browse: function() {
        this.swfu.browse();
    },
    
    startUpload: function() {
        this.swfu.startUpload();
    },
    
    cancelUpload: function(fileId) {
        this.filesToUpload--;
        this.swfu.cancelUpload(fileId);
    },
    
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