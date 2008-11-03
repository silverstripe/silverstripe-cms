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
			var fieldID = form.id + '_' + fieldName;
	    
			var updateURL = form.action + '/field/' + fieldName + '?ajax=1';
			for(var index = 0; index < this.inputFields.length; index++) {
				if(this.inputFields[index].tagName) {
					updateURL += '&' + this.inputFields[index].name + '=' + encodeURIComponent(this.inputFields[index].value);
				}
			}
			
			updateURL += ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '');

			new Ajax.Updater(fieldID, updateURL, {
				onComplete: function() {
					Behaviour.apply($(fieldID), true);
				},
				onFailure: function(response) {
					errorMessage('Could not filter results: ' + response.responseText );
				}
			});
		} catch(er) {
			errorMessage('Error searching');
		}

		return false;	
	}
}