/**
 * This class is used for upload in TinyMCE editor.
 * If one of methods is not commented look for comment in Upload.js.
*/
TinyMCEImageEnhancement = Class.create();
TinyMCEImageEnhancement.prototype = {
	initialize: function() {
		this.filesUploaded = 0;
		this.processInProgress = false;
		Event.observe(window,'load',this.onWindowLoad.bind(this));
	},
	
	addListeners: function() {
		$('Form_EditorToolbarImageForm_FolderID').value = "";
		Event.observe($('AddFolder'),'click',this.onAddFolder.bind(this));
		Event.observe($('FolderOk'),'click',this.onFolderOk.bind(this));
		Event.observe($('FolderCancel'),'click',this.onFolderCancel.bind(this)); 
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
	
	/**
	 * Called on window.onload
	*/
	
	onWindowLoad: function() {
		// Due to a bug in the flash plugin on Linux and Mac, 
		 //we need at least version 9.0.64 to use SWFUpload
		// see http://open.silverstripe.com/ticket/3023
	   pv = getFlashPlayerVersion();
	   if(pv.major < 9 || pv.major > 9 || (pv.major == 9 && pv.minor == 0 && pv.rev < 64)) {
		  if($('AddFolderGroup')) $('AddFolderGroup').style.display = 'none';
		  if($('PipeSeparator')) $('PipeSeparator').style.display = 'none';
		  if($('UploadGroup')) $('UploadGroup').style.display = 'none';
		  return;
	   }
	
		if($('FolderID') != null) {
			if($('SecurityID')) var securityid=$('SecurityID').value;
			else var securityid=null;
			this.upload = new Upload(
				{
				   fileTypes : '*.jpeg;*.jpg;*.jpe;*.png;*.gif;',
				   fileTypesDescription : 'Image files',
				   fileUploadLimit : '100',
				   securityID : securityid,
				   beginUploadOnQueue : true,
				   buildUI : this.addListeners.bind(this),
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
		this.upload.startUpload();
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
			$('Image').ajaxGetFiles(this.getParentID(), null, this.insertImages.bind(this));	
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
tinyMCEImageEnhancement = new TinyMCEImageEnhancement();
