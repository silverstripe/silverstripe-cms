AssetTableField = Class.create();
AssetTableField.applyTo('#Form_EditForm_Files');
AssetTableField.prototype = {
	
	initialize: function() {
		Behaviour.register({
			'#Form_EditForm div.FileFilter input' : {
				onkeypress : this.prepareSearch.bind(this)
			},

			'#Form_EditForm' : {
				changeDetection_fieldsToIgnore : {
					'ctf[start]' : true,
					'ctf[ID]' : true,
					'FileFilterButton' : true,
					'FileFieldName' : true,
					'FileSearch' : true
				}
			}
		});
	},
	
	// prevent submission of wrong form-button (FileFilterButton)
	prepareSearch: function(e) {
		// IE6 doesnt send an event-object with onkeypress
		var event = (e) ? e : window.event;
		var keyCode = (event.keyCode) ? event.keyCode : event.which;
		
		if(keyCode == Event.KEY_RETURN) {
			var el = Event.element(event);
			$('FileFilterButton').onclick(event);
			Event.stop(event);
			return false;
		}
	}
}

FileFilterButton = Class.create();
FileFilterButton.applyTo('#FileFilterButton');
FileFilterButton.prototype = {
	initialize: function() {
		this.inputFields = new Array();
		
		var childNodes = this.parentNode.parentNode.getElementsByTagName('input');
		
		for( var index = 0; index < childNodes.length; index++ ) {
			if( childNodes[index].tagName ) {
				childNodes[index].resetChanged = function() { return false; }
				childNodes[index].isChanged = function() { return false; }
				this.inputFields.push( childNodes[index] );
			}
		}
		
		childNodes = this.parentNode.getElementsByTagName('select');
		
		for( var index = 0; index < childNodes.length; index++ ) {
			if( childNodes[index].tagName ) {
				childNodes[index].resetChanged = function() { return false; }
				childNodes[index].field_changed = function() { return false; }
				this.inputFields.push( childNodes[index] );
			}
		}
	},
	
	isChanged: function() {
		return false;
	},
	
	onclick: function(e) {
		if(!$('ctf-ID') || !$('FileFieldName')) {
			return false;
		}

		try {
			var form = Event.findElement(e, 'form');
			var fieldName = $('FileFieldName').value;
	    
			// build url
			var updateURL = form.action + '/field/' + fieldName + '?';
			for(var index = 0; index < this.inputFields.length; index++) {
				if(this.inputFields[index].tagName) {
					updateURL += '&' + this.inputFields[index].name + '=' + encodeURIComponent(this.inputFields[index].value);
				}
			}
			updateURL += ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '');

			// update the field
			var field = form.getElementsByClassName('AssetTableField')[0];
			field.setAttribute('href', updateURL);
			field.refresh();
		} catch(er) {
			errorMessage('Error searching');
		}

		return false;	
	}
}

MarkingPropertiesButton = Class.create();
MarkingPropertiesButton.applyTo(
	'#Form_EditForm_deletemarked', 
	"Please select some files to delete!", 'deletemarked', 'Do you really want to delete the marked files?'
);

MarkingPropertiesButton.prototype = {
	initialize: function(noneCheckedError, action, confirmMessage) {
		this.noneCheckedError = noneCheckedError;
		this.action = action;
		this.confirmMessage = confirmMessage;
	},
	
	onclick: function() {
		var i, list = "", checkboxes = $('Form_EditForm').elements['Files[]'];
		if(!checkboxes) checkboxes = [];
		if(!checkboxes.length) checkboxes = [ checkboxes ];
		for(i=0;i<checkboxes.length;i++) {
			if(checkboxes[i].checked) list += (list?',':'') + checkboxes[i].value;
		}
		
		if(list == "") {
			alert(this.noneCheckedError);
			return false;
			
		} else {
			$('Form_EditForm_FileIDs').value = list;
		}
		// If there is a confirmation message, show it before submitting
		if('' != this.confirmMessage) {
			// Only submit if OK button is clicked
			if (confirm(this.confirmMessage)) {
				$('Form_EditForm').save(false, null, this.action);
			}
		} else {
			$('Form_EditForm').save(false, null, this.action);
		}
		return false;
	}
}