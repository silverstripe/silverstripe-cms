/**
 * This class is used for upload in TinyMCE editor.
 * If one of methods is not commented look for comment in Upload.js.
*/
TinyMCEImageEnhancement = Class.create();
TinyMCEImageEnhancement.prototype = {
    initialize: function() {
        this.filesUploaded = 0;
        this.processInProgress = false;

        Event.observe($('AddFolder'),'click',this.onAddFolder.bind(this));
        Event.observe($('FolderOk'),'click',this.onFolderOk.bind(this));
        Event.observe($('FolderCancel'),'click',this.onFolderCancel.bind(this));

		this.onLoad();
    },   
	
	onLoad: function() {
		this.upload = new Upload({
			fileUploadLimit : '6',
			button_image_url : 'cms/images/swf-upload-button-small.jpg',
			button_width : 59,
			button_height: 18,
			fileQueued: this.uploadFileQueuedCallback.bind(this),
			fileComplete: this.uploadFileCompleteCallback.bind(this),
			queueComplete: this.uploadQueueCompleteCallback.bind(this)
		});
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
	 * The user clicks the "ok" anchor link, the click event calls up
	 * this function which creates a new AJAX request to add a new folder
	 * using the addfolder function in AssetAdmin.php (admin/assets/addfolder).
	 */
	onFolderOk: function(event) {
		Event.stop(event);
		var folderName = $('NewFolderName').value;
		var options = {
			method: 'post',
			postBody: 'ParentID=' + this.getParentID() + '&ajax=1&returnID=1&Name=' + folderName + ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : ''),
			onSuccess: this.onFolderGetSuccess.bind(this),
			onFailure: function(transport) {
				errorMessage('Error: Folder not added', transport); 
			}
		};
		
		new Ajax.Request('admin/assets/addfolder', options);
	},
    
	/**
	 * If the "addFolderOk" function does a successful AJAX post, call this
	 * function. Take the folder ID that was created in "addFolderOk"
	 * via ajax and send data to modify that folder record.
	 */    
	onFolderGetSuccess: function(transport) {
		var folderID = transport.responseText;
		
		var date = new Date();
		var year = date.getFullYear();
		var month = date.getMonth() < 10 ? '0' + date.getMonth() : date.getMonth();  
		var day = date.getDay() < 10  ? '0' + date.getDay() : date.getDay();
		var hours = date.getHours() < 10  ? '0' + date.getHours() : date.getHours();
		var minutes = date.getMinutes() < 10  ? '0' + date.getMinutes() : date.getMinutes();
		var seconds = date.getSeconds() < 10  == 1 ? '0' + date.getSeconds() : date.getSeconds();
		var currentDate = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds; 
		
		var folderName = $('NewFolderName').value;
		
		this.folderID = folderID;
		
		statusMessage('Creating new folder');
        $('TreeDropdownField_Form_EditorToolbarImageForm_FolderID').itemTree = null;
        $('TreeDropdownField_Form_EditorToolbarImageForm_FolderID').setValue(this.folderID);
        $('NewFolderName').value = '';
        Element.show('AddFolder');
        Element.hide('NewFolderName','FolderOk','FolderCancel');
        this.removeIE6Hack();
    },
    
    /**
     * If user doesn't want to add folder return to default UI. 
    */
    
    onFolderCancel: function(event) {
        $('NewFolderName').value = '';
        Element.show('AddFolder');
        Element.hide('NewFolderName','FolderOk','FolderCancel');
        this.removeIE6Hack();
		Event.stop(event);
		return false;
    },
    
	uploadFileQueuedCallback: function(file,queueLength) {
		if(this.getParentID() == "root") {
			statusMessage("Please choose folder","bad");	
		}
		else {
			this.processInProgress = true;
			this.upload.swfu.addPostParam('FolderID', this.getParentID());
			this.upload.swfu.addFileParam(file.id,'ID',this.folderID);
			this.upload.swfu.addFileParam(file.id,'Files',file.name);
			$('UploadFiles').innerHTML = "Uploading Files...("+ this.filesUploaded +")";	
			this.upload.swfu.startUpload(file.id);		
		}
	},
	
	uploadFileCompleteCallback: function(file,serverData) {
		this.filesUploaded++;
		$('UploadFiles').innerHTML = 'Uploading Files..... ('+ this.filesUploaded +")";
	},
	
	uploadQueueCompleteCallback: function(serverData) {
		this.filesUploaded = this.upload.getFilesUploaded();
		$('UploadFiles').innerHTML = "";
		statusMessage('Uploaded Files Successfully','good');
		
		$('FolderImages').ajaxGetFiles(this.getParentID(), null);	
	},
	
	/**
	 * Iterates over all uploaded images and add them to TinyMCE editor
	 *
	 * @param transport object
	*/
	insertImages: function(transport) {
		//HACK FOR STRANGE ERROR OCCURING UNDER SAFARI
		if(transport.responseText == '') {
			$('Image').ajaxGetFiles(this.getParentID(), null, this.insertImages.bind(this));
			return;
		}
		//END OF HACK

        $('Image').reapplyBehaviour();

        this.addToTinyMCE = this.addToTinyMCE.bind(this);
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
        TinyMCE_AdvancedTheme._insertImage(relativeHref, altText, 0, '', '', destWidth, destHeight, '', '', cssClass);
    },
    
    /**
     * Under IE6 when we click on "add folder" anchor, rest of anchors loose their correct position
     *
    */
    
    applyIE6Hack: function() {
        if(/msie/i.test(navigator.userAgent)) {
	        elements = [$('FolderOk'),$('FolderCancel'),$('UploadFiles')];
	        $A(elements).each(function(element) {
	            element.style.position = "relative";
	            element.style.top = "-3px";
	        });
	   }
    },
    
    removeIE6Hack: function() {
        if(/msie/i.test(navigator.userAgent)) {
	        elements = [$('FolderOk'),$('FolderCancel'),$('UploadFiles')];
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
var tinyMCEImageEnhancement;

jQuery(function() {
	tinyMCEImageEnhancement = new TinyMCEImageEnhancement();
});
