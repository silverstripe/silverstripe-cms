ThumbnailStripField = Class.create();
// We do this instead of div.thumbnailstrip for efficiency.  It means that ThumbnailStripField can only be used in the
// CMS toolbar
ThumbnailStripField.applyTo('#FolderImages');
ThumbnailStripField.applyTo('#Flash');
ThumbnailStripField.prototype = {
	
	/**
	 * @var updateMethod string Specifies the Ajax-call for getting files
	 * (currently either "getimages" or "getflash"). This can be specified
	 * in the PHP-constructor of ThumbnailStripField and is passed to the client
	 * as a fake css-class.
	 */
	updateMethod: 'getimages',
	
	initialize: function() {
		try {
			this.updateMethod = this.className.match(/updatemethod=([^ ]+)/)[1];
		} catch(err) {}	
		
		if(this.className.match(/parent=([^ ]+)/)) {
			// HACK: This is hard-coded to only work with TreeDropdownFields
			var parentField = $(RegExp.$1).parentNode;
			if(parentField) {
				parentField.observeMethod('Change', this.ajaxGetFiles.bind(this));
			}
			
			var searchField = $$('#' + this.updateMethod + 'Search input')[0];		
			var timeout = undefined;
			
			if(searchField) {
				Event.observe(searchField, 'keypress', function(event) {
					if(timeout != undefined) clearTimeout(timeout);
					
					timeout = setTimeout(function() {
						var searchText = searchField.value;
						var folderID = null;
						if (parentField && parentField.inputTag) 
							folderID = parentField.inputTag.value
						$('Flash').ajaxGetFiles(folderID, searchText);
						$('FolderImages').ajaxGetFiles(folderID, searchText);
					}, 500);
				});
			}
		}
	},
	
	ajaxGetFiles: function(folderID, searchText, callback) {
		if(!callback) callback = this.reapplyBehaviour.bind(this);
		var securityID = ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '');
		this.innerHTML = '<h2>Loading...</h2>';
		var ajaxURL = this.helperURLBase() + '&methodName=' + this.updateMethod + '&folderID=' + folderID + '&searchText=' + searchText + securityID + '&cacheKillerDate=' + parseInt((new Date()).getTime()) + '&cacheKillerRand=' + parseInt(10000 * Math.random());

		new Ajax.Updater(this, ajaxURL, {
			method : 'get', 
			onComplete : callback,
			onFailure : function(response) { errorMessage("Error getting files", response); }
		});
	},

	reapplyBehaviour: function() {
		Behaviour.apply(this);
	},

	helperURLBase: function() {
		var fieldName = this.id;
		var ownerForm = this.ownerForm();
		var securityID = ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '');
		
		return ownerForm.action + '?action_callfieldmethod=1&fieldName=' + fieldName + '&ajax=1' + securityID;
	},
	
	ownerForm: function() {
		var f =this.parentNode;
		while(f && f.tagName.toLowerCase() != 'form') f = f.parentNode;
		return f;
	}
	
}
