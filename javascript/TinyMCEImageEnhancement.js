TinyMCEImageEnhancement = Class.create();
TinyMCEImageEnhancement.prototype = {
    initialize: function() {
        this.fileUploaded = 0;
        this.filesToUpload = 0;
        Event.observe(window,'load',this.onWindowLoad.bind(this));
    },
    
    buildUI: function() {
        $('Form_EditorToolbarImageForm_FolderID').value = "";
        
        var divAddFolder = document.createElement('div');
        divAddFolder.id = "AddFolderGroup";
        Element.addClassName(divAddFolder,'group');
        
        
        var addFolder = document.createElement('a');
        addFolder.id = 'AddFolder';
        addFolder.href = '#';
        addFolder.innerHTML = 'add folder';
        Element.addClassName(addFolder,'link');
        divAddFolder.appendChild(addFolder);
  
        var newFolderName = document.createElement('input');
        newFolderName.id = 'NewFolderName';
        newFolderName.type = 'text';
        Element.addClassName(newFolderName,'addFolder');
        divAddFolder.appendChild(newFolderName);
        Element.hide(newFolderName);

        var folderOk = document.createElement('a');
        folderOk.id = 'FolderOk';
        folderOk.href= '#';
        folderOk.innerHTML = 'ok';
        Element.addClassName(folderOk,'addFolder');
        Element.addClassName(folderOk,'link');
        divAddFolder.appendChild(folderOk);
        Element.hide(folderOk);
        
        var folderCancel = document.createElement('a');
        folderCancel.id = 'FolderCancel';
        folderCancel.href = '#';
        folderCancel.innerHTML = 'cancel';
        Element.addClassName(folderCancel,'addFolder');
        Element.addClassName(folderCancel,'link');
        divAddFolder.appendChild(folderCancel);
        Element.hide(folderCancel);
        
        $('FolderID').appendChild(divAddFolder);
        
        var divUpload = document.createElement('div');
        divUpload.id = "UploadGroup";
        Element.addClassName(divUpload,'group');
        
        var pipeSeparator = document.createElement('div');
        pipeSeparator.id = "PipeSeparator";
        pipeSeparator.innerHTML = " | ";
        pipeSeparator.style.display = "inline";
        divUpload.appendChild(pipeSeparator); 
        
        var uploadFiles = document.createElement('a');
        uploadFiles.id = 'UploadFiles';
        uploadFiles.href = '#';
        uploadFiles.innerHTML = 'upload';
        Element.addClassName(uploadFiles,'link');
        divUpload.appendChild(uploadFiles);
        
        $('FolderID').appendChild(divUpload);

        Event.observe(addFolder,'click',this.onAddFolder.bind(this));
        Event.observe(folderOk,'click',this.onFolderOk.bind(this));
        Event.observe(folderCancel,'click',this.onFolderCancel.bind(this));    
        this.onUpload = this.onUpload.bind(this);
        Event.observe($('UploadFiles'),'click',this.onUpload);
        
                                            
    },   
    
    onUpload: function(event) {
        Event.stop(event);
        if(!this.uploadInProgress) {
	        var parentID = $('Form_EditorToolbarImageForm_FolderID').value == '' ? 'root' : $('Form_EditorToolbarImageForm_FolderID').value;
	        if(parentID != 'root') {
	            swfu.browse();
	            this.uploadInProgress = true;
	        } else {
	            statusMessage("Please choose folder","bad");                                                
	        }
	   }
    },
    
    onAddFolder: function(event) {
        Event.stop(event);
        Element.hide('AddFolder');
        Element.show('NewFolderName','FolderOk','FolderCancel');
        this.applyIE6Hack();
    },
    
    onFolderOk: function(event) {
        Event.stop(event);
        var parentID = $('Form_EditorToolbarImageForm_FolderID').value == '' ? 'root' : $('Form_EditorToolbarImageForm_FolderID').value;
        var folderName = $('NewFolderName').value;
        var options = {
            method: 'post',
            postBody: 'ParentID=' + parentID + '&ajax=1' ,
            onSuccess: this.onFolderGetSuccess.bind(this),
            onFailure: function(transport) {
                           errorMessage('Error: Folder not added', transport); 
                       }
         };
         new Ajax.Request('admin/assets/addfolder', options);
               
        
    },
    
    onFolderGetSuccess: function(transport) {
        var t1 = transport.responseText.indexOf('TreeNode(');
        var t2 = transport.responseText.indexOf(',');
        var folderID = transport.responseText.substring(t1 + 9,t2);
        var date = new Date();
        var year = date.getFullYear();
        var month = date.getMonth() < 10 ? '0' + date.getMonth() : date.getMonth();  
        var day = date.getDay() < 10  ? '0' + date.getDay() : date.getDay();
        var hours = date.getHours() < 10  ? '0' + date.getHours() : date.getHours();
        var minutes = date.getMinutes() < 10  ? '0' + date.getMinutes() : date.getMinutes();
        var seconds = date.getSeconds() < 10  == 1 ? '0' + date.getSeconds() : date.getSeconds();
        var currentDate = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds; 
        var parentID = $('Form_EditorToolbarImageForm_FolderID').value == '' ? 'root' : $('Form_EditorToolbarImageForm_FolderID').value;
        var folderName = $('NewFolderName').value;
        var options = {
            method: 'post',
            postBody: 'Created=' + currentDate + '&Name=' + folderName + '&ClassName=Folder&ID=' + folderID + '&ajax=1&action_save=1',
            onSuccess: this.onFolderAddSuccess.bind(this),
            onFailure: function(transport) {
                           errorMessage('Error: Folder not added', transport); 
                       }
        };
        new Ajax.Request('admin/assets/index/' + parentID + '?executeForm=EditForm', options);
    },
    
    onFolderAddSuccess: function(transport) {
        statusMessage('Creating new folder');
        document.getElementsBySelector("div.TreeDropdownField.single")[2].itemTree = null;
        $('NewFolderName').value = '';
        Element.show('AddFolder');
        Element.hide('NewFolderName','FolderOk','FolderCancel');
        this.removeIE6Hack();                               
    },
    
    onFolderCancel: function(event) {
        Event.stop(event);
        $('NewFolderName').value = '';
        Element.show('AddFolder');
        Element.hide('NewFolderName','FolderOk','FolderCancel');
        this.removeIE6Hack();
    },
    
    
    
    onWindowLoad: function() {
        if($('FolderID') != null) {
	        path = this.getBasePath();
	        sessId = this.getSessionId();
	        swfu = new SWFUpload({
	                upload_target_url: path + '/assets/index/root?executeForm=UploadForm&PHPSESSID=' + sessId,   // Relative to the SWF file
	                file_post_name: 'Files',
	                // Flash file settings
	                file_size_limit : '1024000',  // 10 MB
	                file_types : '*.jpeg;*.jpg;*.jpe;*.png;*.gif;', // or you could use something like: '*.doc;*.wpd;*.pdf',
	                file_types_description : 'Image files',
	                file_upload_limit : '100',
	                //file_queue_limit : '1', // this isn't needed because the upload_limit will automatically place a queue limit
	                begin_upload_on_queue : true,
	                use_server_data_event : true,
	                validate_files: false,
	                // Event handler settings
	                file_queued_handler : this.uploadFileQueuedCallback.bind(this),
	                file_validation_handler : Prototype.emptyFunction,
	                file_progress_handler : this.uploadProgressCallback.bind(this),
	                file_cancelled_handler : Prototype.emptyFunction.bind(this),
	                file_complete_handler : this.uploadFileCompleteCallback.bind(this),
	                queue_complete_handler : this.uploadQueueCompleteCallback.bind(this),
	                error_handler : this.uploadErrorCallback.bind(this),
	                dialog_cancelled_handler: this.dialogCancelCallback.bind(this),
	                // Flash Settings
	                flash_url : 'jsparty/SWFUpload/SWFUpload.swf',    // Relative to this file
	                // UI settings
	                ui_function: this.buildUI.bind(this),
	                ui_container_id : '',
	                degraded_container_id : '',
	                // Debug settings
	                debug: false
	            });
	    }
    },
    
    dialogCancelCallback: function() {
        this.uploadInProgress = false;
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
    
    uploadFileQueuedCallback: function(file,queueLength) {
        this.filesToUpload++;
        this.addFileParam(file);
        $('UploadFiles').innerHTML = "Uploading ... 1/" + this.filesToUpload;    
    },
    
    uploadProgressCallback: function(file,bytesLoaded) {
    },
    
    uploadFileCompleteCallback: function(file,serverData) {
        this.fileUploaded++;
        Element.addClassName($('UploadFiles'),'link');
        $('UploadFiles').innerHTML = 'Uploading ... ' + this.fileUploaded + "/" + this.filesToUpload;
    },
    
    uploadQueueCompleteCallback: function() {
        $('UploadFiles').innerHTML = "upload";
        statusMessage('Uploaded ' + this.fileUploaded + ' files','good');
        var parentID = $('Form_EditorToolbarImageForm_FolderID').value == '' ? 'root' : $('Form_EditorToolbarImageForm_FolderID').value;
        if(parentID != 'root') {
            $('Image').ajaxGetFiles(parentID,this.insertImages.bind(this));    
        }
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
        var parentID = $('Form_EditorToolbarImageForm_FolderID').value == '' ? 'root' : $('Form_EditorToolbarImageForm_FolderID').value;
        swfu.addFileParam(file.id,'ID',parentID);
        swfu.addFileParam(file.id,'action_doUpload','1');
        swfu.addFileParam(file.id,'Files',file.name);
        swfu.addFileParam(file.id,'MAX_FILE_SIZE','1073741824');
    },
    
    insertImages: function() {
        this.uploadInProgress = false;
        $('Image').reapplyBehaviour();
        this.addToTinyMCE = this.addToTinyMCE.bind(this);
        var childNodes = $('Image').childNodes[0].childNodes;
        var newImages = $A(childNodes).slice(childNodes.length - this.fileUploaded);
        newImages.each(function(item) {
            tinyMCEImageEnhancement.addToTinyMCE(item.childNodes[0]);
        });
        this.fileUploaded = 0;
        this.filesToUpload = 0;
            
    },
    
    addToTinyMCE: function(target) {
		var formObj = $('Form_EditorToolbarImageForm');
        var altText = formObj.elements.AltText.value;
        var cssClass = formObj.elements.CSSClass.value;
        var baseURL = document.getElementsByTagName('base')[0].href;
        var relativeHref = target.href.substr(baseURL.length)
        if(!tinyMCE.selectedInstance) tinyMCE.selectedInstance = Toolbar.instance().editor;
        if(tinyMCE.selectedInstance.contentWindow.focus) tinyMCE.selectedInstance.contentWindow.focus();
        // Extract dest width and dest height from the class name 
        var destWidth = null;
        var destHeight = null;
        try {
            var imgTag = target.getElementsByTagName('img')[0];
            destWidth = imgTag.className.match(/destwidth=([0-9.\-]+)([, ]|$)/) ? RegExp.$1 : null;
            destHeight = imgTag.className.match(/destheight=([0-9.\-]+)([, ]|$)/) ? RegExp.$1 : null;
        } catch(er) {
        }
        TinyMCE_AdvancedTheme._insertImage(relativeHref, altText, 0, null, null, destWidth, destHeight, null, null, cssClass);
    },
    
    applyIE6Hack: function() {
        if(BrowserDetect.browser == 'Explorer') {
	        elements = [$('FolderOk'),$('FolderCancel'),$('UploadFiles'),$('PipeSeparator')];
	        $A(elements).each(function(element) {
	            element.style.position = "relative";
	            element.style.top = "-3px";
	        });
	   }
    },
    
    removeIE6Hack: function() {
        if(BrowserDetect.browser == 'Explorer') {
	        elements = [$('FolderOk'),$('FolderCancel'),$('UploadFiles'),$('PipeSeparator')];
	        $A(elements).each(function(element) {
	            element.style.position = "";
	        });
	    }
    }
}
tinyMCEImageEnhancement = new TinyMCEImageEnhancement();