ThumbnailStripField = Class.create();
// We do this instead of div.thumbnailstrip for efficiency.  It means that ThumbnailStripField can only be used in the
// CMS toolbar
ThumbnailStripField.applyTo('#Image');
ThumbnailStripField.applyTo('#Flash');
ThumbnailStripField.prototype = {
	
	/**
	 * @var updateMethod string Specifies the Ajax-call for getting files
	 * (currently either "getimages" or "getflash"). This can be specified
	 * in the PHP-constructor of ThumbnailStripField and is passed to the client
	 * as a fake css-class.
	 */
	updateMethod: "getimages",
	
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
		}
	},
	
	ajaxGetFiles: function(folderID,callback) {
		if(!callback) callback = this.reapplyBehaviour.bind(this);
		this.innerHTML = '<span style="float: left">Loading...</span>'
		var ajaxURL = this.helperURLBase() + '&methodName='+this.updateMethod+'&folderID=' + folderID;
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
		var fieldName = this.id; //this.id.replace(this.ownerForm().name + '_','');
		
		return this.ownerForm().action + '&action_callfieldmethod=1&fieldName=' + fieldName + '&ajax=1'
	},
	
	ownerForm: function() {
		var f =this.parentNode;
		while(f && f.tagName.toLowerCase() != 'form') f = f.parentNode;
		return f;
	}
	
}