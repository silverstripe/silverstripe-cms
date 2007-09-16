var Upload = {
    initialize: function() {
        iframe = window.top.document.getElementById('AssetAdmin_upload');
        Upload.fileUploaded = 0;
        Upload.uploadMessage = '';
        Upload.onLoad();
    },
    
    onLoad: function() {
        path = 'http://' + window.location.host + window.location.pathname;
        if(path[path.length-1] == '/') path = path.substring(0,path.length-1);
        var start = document.cookie.indexOf('PHPSESSID')+10;
        var end = document.cookie.indexOf(';',start);
        if(end == -1) end = document.cookie.length;
        sessId = document.cookie.substring(start,end);
        swfu = new SWFUpload({
                upload_target_url: path + '/index/root?executeForm=UploadForm&PHPSESSID=' + sessId,   // Relative to the SWF file
                file_post_name: 'Files',
                // Flash file settings
                file_size_limit : '1024000',  // 10 MB
                file_types : '*.*', // or you could use something like: '*.doc;*.wpd;*.pdf',
                file_types_description : 'All Files',
                file_upload_limit : '6',
                //file_queue_limit : '1', // this isn't needed because the upload_limit will automatically place a queue limit
                begin_upload_on_queue : false,
                use_server_data_event : true,
                validate_files: false,
                // Event handler settings
                file_queued_handler : Upload.uploadFileQueuedCallback,
                file_validation_handler : Prototype.emptyFunction,
                file_progress_handler : Upload.uploadProgressCallback,
                file_cancelled_handler : Upload.uploadFileCancelCallback,
                file_complete_handler : Upload.uploadFileCompleteCallback,
                queue_complete_handler : Upload.uploadQueueCompleteCallback,
                error_handler : Upload.uploadErrorCallback,
                // Flash Settings
                flash_url : 'jsparty/SWFUpload/SWFUpload.swf',    // Relative to this file
                // UI settings
                ui_function: Upload.extendForm,
                ui_container_id : 'Form_EditForm',
                degraded_container_id : 'Form_EditForm',
                // Debug settings
                debug: false
            });
    },
    
    extendForm: function() {
        if(iframe.contentDocument == undefined) iframe.contentDocument = document.frames[0].document;//IE HACK   
        element = iframe.contentDocument.getElementById('Form_UploadForm');
        inputFile = iframe.contentDocument.getElementById('Form_UploadForm_Files-0');
        inputFileParent = inputFile.parentNode;
        inputFileParent.removeChild(inputFile);
        inputFile = iframe.contentDocument.createElement('input');
        inputFile.type = 'text';
        inputFile.id = 'Form_UploadForm_Files-0';
        inputFileParent.appendChild(inputFile);
        inputButton = iframe.contentDocument.getElementById('Form_UploadForm_Files-1');
        if(inputButton != null) inputButton.parentNode.removeChild(inputButton);
        inputButton = iframe.contentDocument.createElement('input');
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
        Event.observe(inputButton,'click',function() {swfu.browse();});
        Event.observe(iframe.contentDocument.getElementById('Form_UploadForm_action_upload'),'click',function(event) {
                                               swfu.startUpload();
                                               Event.stop(event);
                                           });
    },
    
    uploadFileQueuedCallback: function(file,queueLength) {
        iframe.contentDocument.getElementById('Form_UploadForm_action_upload').disabled = false;
        Upload.addFileParam(file);
        var fileContainer = iframe.contentDocument.getElementById('Form_UploadForm_FilesList');
        if(fileContainer == null) {
           fileContainer = iframe.contentDocument.createElement('div');
           fileContainer.id = 'Form_UploadForm_FilesList';
           iframe.contentDocument.getElementById('Form_UploadForm').appendChild(fileContainer);
        }
        
        var fileToUpload = iframe.contentDocument.createElement('div');
        fileToUpload.id = 'Form_UploadForm_FilesList_' + file.id;
        fileToUpload.style.marginBottom = '3px';
        fileContainer.appendChild(fileToUpload);
        
        var fileName = iframe.contentDocument.createElement('div');
        fileName.id = 'Form_UploadForm_FilesList_Name_' + file.id;
        fileName.style.position = 'relative';
        fileName.style.top = '-4px';
        fileName.style.display = 'inline';
        fileName.style.padding = '2px';
        fileName.innerHTML = file.name;
        fileName.style.height = Element.getDimensions(fileName).height + 1 + 'px';//IE hack
        fileToUpload.appendChild(fileName);
        
        var fileProgress = iframe.contentDocument.createElement('div');
        fileProgress.id = 'Form_UploadForm_FilesList_Progress_' + file.id;
        Position.clone(fileName,fileProgress);       
        fileProgress.style.backgroundColor = 'black';
        fileProgress.style.display = 'inline';
        fileProgress.style.position = 'absolute';
        fileProgress.style.left = '5px';
        fileProgress.style.width = '0px';
        fileProgress.finished = false;        
        switch(BrowserDetect.browser) {
            case 'Explorer':
                fileProgress.style.top = parseInt(fileProgress.style.top) + 6 + 'px';
            break;
            case 'Safari':
                fileProgress.style.top = parseInt(fileProgress.style.top) + 4 + 'px';
            break;
            case 'Firefox':  
                fileProgress.style.top = parseInt(fileProgress.style.top) + 8 + 'px';
            break;
        }
        fileProgress.style.height = Element.getDimensions(fileName).height + 1 + 'px';        
        fileToUpload.appendChild(fileProgress);
        
        var fileDelete = iframe.contentDocument.createElement('input');
        fileDelete.id = file.id;
        fileDelete.type = 'button';
        fileDelete.value = 'Delete';
        Element.addClassName(fileDelete,'delete');
        fileToUpload.appendChild(fileDelete);
        Event.observe(fileDelete,'click',Upload.uploadFileCancelCallback);
    },
    
    uploadProgressCallback: function(file,bytesLoaded) {
        fileName = iframe.contentDocument.getElementById('Form_UploadForm_FilesList_Name_' + file.id);
        fileName.style.border = 'solid 1px black';
        fileProgress = iframe.contentDocument.getElementById('Form_UploadForm_FilesList_Progress_' + file.id);
        fileProgress.style.opacity = 0.3;
        fileProgress.style.filter = 'alpha(opacity=30)';
        if(!fileProgress.cloned) {
            Position.clone(fileName,fileProgress);
            fileProgress.style.width = '0px';
            fileProgress.cloned = true;
        }
        fileProgress.style.width = (bytesLoaded / file.size) * Element.getDimensions(fileName).width - 1 + 'px';
    },
    
    uploadFileCompleteCallback: function(file,serverData) {
        Upload.fileUploaded++;
        toEval = serverData.substr(serverData.indexOf('<script'));
        toEval = toEval.replace('<script type="text/javascript">','');
        toEval = toEval.replace('</script>','');
        Upload.uploadMessage = toEval;
        iframe.contentDocument.getElementById('Form_UploadForm_FilesList_Progress_' + file.id).finished = true;
    },
    
    uploadFileCancelCallback: function(event) {
        element = Event.element(event);
        fileId = element.id;
        swfu.cancelUpload(fileId);
        fileContainer = iframe.contentDocument.getElementById('Form_UploadForm_FilesList');
        elementToDelete = iframe.contentDocument.getElementById('Form_UploadForm_FilesList_' + fileId);
        elementToDelete.parentNode.removeChild(elementToDelete);
        filesToUpload = fileContainer.childNodes.length;
        if(filesToUpload > 0) {
            iframe.contentDocument.getElementById('Form_UploadForm_action_upload').disabled = false;
        } else {
            iframe.contentDocument.getElementById('Form_UploadForm_action_upload').disabled = true;
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
        eval(Upload.uploadMessage.replace('1',Upload.fileUploaded));
    },
    
    uploadErrorCallback: function(error_code, file, message) {
        swfu.cancelQueue();
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
	            alert('Files cannot be bigger than 10MB.');
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
        swfu.addFileParam(file.id,'ID',iframe.contentDocument.getElementById('Form_UploadForm_ID').value);
        swfu.addFileParam(file.id,'action_doUpload','1');
        swfu.addFileParam(file.id,'Files',file.name);
        swfu.addFileParam(file.id,'MAX_FILE_SIZE','1073741824');
    }
}
window.top.document.Upload = Upload;