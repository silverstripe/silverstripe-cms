/**
 * If one of methods is not commented look for comment in Upload.js.
*/
TinyMCEImageEnhancement = Class.create();
TinyMCEImageEnhancement.prototype = {
    initialize: function() {
        this.filesUploaed = 0;
        this.processInProgress = false;
        Event.observe(window,'load',this.onWindowLoad.bind(this));
    },
    
    buildUI: function() {
        $('Form_EditorToolbarImageForm_FolderID').value = "";
        
        divAddFolder = this.addElement('div','group',$('FolderID'),{'id': 'AddFolderGroup'});
        addFolder = this.addElement('a','link',divAddFolder,{'id': 'AddFolder','href' : '#','innerHTML': 'add folder'});
        newFolderName = this.addElement('input', 'addFolder',divAddFolder,{'id': 'NewFolderName','type' : 'text'});
        Element.hide(newFolderName);
        var folderOk = this.addElement('a','link',divAddFolder,{'id': 'FolderOk','href' : '#','innerHTML': 'ok'});
        Element.hide(folderOk);
        Element.addClassName(folderOk,'addFolder');
        var folderCancel = this.addElement('a','link',divAddFolder,{'id': 'FolderCancel','href' : '#','innerHTML': 'cancel'});
        Element.hide(folderCancel);
        Element.addClassName(folderCancel,'addFolder');
        
        var divUpload = this.addElement('div','group',$('FolderID'),{'id': 'UploadrGroup'});
        var pipeSeparator = this.addElement('div','',divUpload,{'id' : 'PipeSeparator','innerHTML' : ' | '});
        pipeSeparator.style.display = "inline";
        var uploadFiles = this.addElement('a','link',divUpload,{'id' : 'UploadFiles','href' : '#','innerHTML' : 'upload'}); 
        
        Event.observe(addFolder,'click',this.onAddFolder.bind(this));
        Event.observe(folderOk,'click',this.onFolderOk.bind(this));
        Event.observe(folderCancel,'click',this.onFolderCancel.bind(this));    
        Event.observe($('UploadFiles'),'click',this.onUpload.bind(this));
    },   
    
    /**
     * Method creates HTML element, only reason for this method is DRY. 
    */
    
    addElement: function(tag, className, parent, properties) {
        var e = document.createElement(tag);
        Element.addClassName(e,className);
        parent.appendChild(e);
        Object.extend(e,properties);
        return e;
    },
        
    onUpload: function(event) {
        Event.stop(event);
        if(!this.processInProgress) {
	        if(this.getParentID() != 'root') {
	            this.upload.browse();
	        } else {
	            statusMessage("Please choose folder","bad");                                                
	        }
	    }
    },
    
    /**
     * Called when user clicks "add folder" anchor. 
    */
    
    onAddFolder: function(event) {
        Event.stop(event);
        Element.hide('AddFolder');
        Element.show('NewFolderName','FolderOk','FolderCancel');
        this.applyIE6Hack();
    },
    
    /**
     * Called when user clicks "ok" anchor/ adds folder with given name. 
    */
    
    onFolderOk: function(event) {
        Event.stop(event);
        var folderName = $('NewFolderName').value;
        var options = {
            method: 'post',
            postBody: 'ParentID=' + this.getParentID() + '&ajax=1' ,
            onSuccess: this.onFolderGetSuccess.bind(this),
            onFailure: function(transport) {
                           errorMessage('Error: Folder not added', transport); 
                       }
         };
         new Ajax.Request('admin/assets/addfolder', options);
               
        
    },
    
    /**
     * Method retrieves folder id and sends request which changes folder name. 
    */
    
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
        var folderName = $('NewFolderName').value;
        var options = {
            method: 'post',
            postBody: 'Created=' + currentDate + '&Name=' + folderName + '&ClassName=Folder&ID=' + folderID + '&ajax=1&action_save=1',
            onSuccess: this.onFolderAddSuccess.bind(this),
            onFailure: function(transport) {
                           errorMessage('Error: Folder not added', transport); 
                       }
        };
        new Ajax.Request('admin/assets/index/' + this.getParentID() + '?executeForm=EditForm', options);
    },
    
    /**
     * When folder name has been changed we need to refresh folder list and return to initial state of UI. 
    */
    
    onFolderAddSuccess: function(transport) {
        statusMessage('Creating new folder');
        document.getElementsBySelector("div.TreeDropdownField.single")[2].itemTree = null;
        $('NewFolderName').value = '';
        Element.show('AddFolder');
        Element.hide('NewFolderName','FolderOk','FolderCancel');
        this.removeIE6Hack();                               
    },
    
    /**
     * If user doesn't want to add folder return to default UI. 
    */
    
    onFolderCancel: function(event) {
        Event.stop(event);
        $('NewFolderName').value = '';
        Element.show('AddFolder');
        Element.hide('NewFolderName','FolderOk','FolderCancel');
        this.removeIE6Hack();
    },
    
    /**
     * Called on window.onload
    */
    
    onWindowLoad: function() {
        if($('FolderID') != null) {
	        this.upload = new Upload(
	            {
	               fileTypes : '*.jpeg;*.jpg;*.jpe;*.png;*.gif;',
	               fileTypesDescription : 'Image files',
	               fileUploadLimit : '100',
	               beginUploadOnQueue : true,
                   buildUI : this.buildUI.bind(this),
                   fileQueued : this.uploadFileQueuedCallback.bind(this),
                   fileComplete : this.uploadFileCompleteCallback.bind(this),
                   queueComplete : this.uploadQueueCompleteCallback.bind(this)
	            }        
            );
        }
    },
    
    uploadFileQueuedCallback: function(file,queueLength) {
        this.processInProgress = true;
        this.upload.setFolderID(this.getParentID()); 
        $('UploadFiles').innerHTML = "Uploading ... 1/" + this.upload.getFilesToUpload();    
    },
    
    uploadFileCompleteCallback: function(file,serverData) {
        Element.addClassName($('UploadFiles'),'link');//Safari hack
        $('UploadFiles').innerHTML = 'Uploading ... ' + this.upload.getFilesUploaded() + "/" + this.upload.getFilesToUpload();
    },
    
    uploadQueueCompleteCallback: function() {
        this.filesUploaded = this.upload.getFilesUploaded();
        $('UploadFiles').innerHTML = "upload";
        statusMessage('Uploaded ' + this.upload.getFilesUploaded() + ' files','good');
        if(this.getParentID() != 'root') {
            $('Image').ajaxGetFiles(this.getParentID(),this.insertImages.bind(this));    
        }
    },
    
    /**
     * Iterates over all uploaded images and add them to TinyMCE editor
     *
     * @param transport object
    */
    
    insertImages: function(transport) {
        //HACK FOR STRANGE ERROR OCCURING UNDER SAFARI
        if(transport.responseText == '') {
            $('Image').ajaxGetFiles(this.getParentID(),this.insertImages.bind(this));
            return;
        }
        //END OF HACK
        $('Image').reapplyBehaviour();
        this.addToTinyMCE = this.addToTinyMCE.bind(this);
        var childNodes = $('Image').childNodes[0].childNodes;
        var newImages = $A(childNodes).slice(childNodes.length - this.filesUploaded);
        newImages.each(function(item) {
            tinyMCEImageEnhancement.addToTinyMCE(item.childNodes[0]);
        });
        this.processInProgress = false;
    },
    
    /**
     * Adds particular image to TinyMCE. Most of code has been copied from tiny_mce_improvements.js / ImageThumbnail.onclick
     * Sorry for not following DRY, I didn't want to break smth in tiny_mce_improvements.
     * 
     *  @param target object
    */
    
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
    
    /**
     * Under IE6 when we click on "add folder" anchor, rest of anchors loose their correct position
     *
    */
    
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
    },
    
    /**
     * Returns id of upload folder.
     *
    */
    
    getParentID: function() {
        return  $('Form_EditorToolbarImageForm_FolderID').value == '' ? 'root' : $('Form_EditorToolbarImageForm_FolderID').value;
    }
}
tinyMCEImageEnhancement = new TinyMCEImageEnhancement();