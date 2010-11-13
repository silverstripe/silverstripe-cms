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
		
		var rules = {};
		
		// Assume that the delete link uses the deleteRecord method
		rules['#'+this.id+' table.data a.deletelink'] = {onclick: this.deleteRecord.bind(this)};
		
		Behaviour.register('ComplexTableField_'+this.id,rules);
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
	},
	
	
	// Override deleteRecord function, so that we can give warning if there are
	// links to this file. This  is mostly a copy paste from TableField.js
	deleteRecord: function(e) {
		var img = Event.element(e);
		var link = Event.findElement(e,"a");
		var row = Event.findElement(e,"tr");
		
		var linkCount = row.getElementsByClassName('linkCount')[0];
		if(linkCount) linkCount = linkCount.innerHTML;
		
		var confirmMessage = ss.i18n._t('TABLEFIELD.DELETECONFIRMMESSAGE', 'Are you sure you want to delete this record?');
		if(linkCount>0) confirmMessage += ss.i18n.sprintf(ss.i18n._t('TABLEFIELD.DELETECONFIRMMESSAGEV2','\nThere are %s page(s) that use this file, please review the list of pages on the Links tab of the file before continuing.'),linkCount);

		// TODO ajaxErrorHandler and loading-image are dependent on cms, but formfield is in sapphire
		var confirmed = confirm(confirmMessage);
		if(confirmed)
		{
			img.setAttribute("src",'cms/images/network-save.gif'); // TODO doesn't work
			new Ajax.Request(
				link.getAttribute("href"),
				{
					method: 'post', 
					postBody: 'forceajax=1' + ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : ''),
					onComplete: function(){
						Effect.Fade(
							row,
							{
								afterFinish: function(obj) {
									// remove row from DOM
									obj.element.parentNode.removeChild(obj.element);
									// recalculate summary if needed (assumes that TableListField.js is present)
									// TODO Proper inheritance
									if(this._summarise) this._summarise();
									// custom callback
									if(this.callback_deleteRecord) this.callback_deleteRecord(e);
								}.bind(this)
							}
						);
					}.bind(this),
					onFailure: this.ajaxErrorHandler
				}
			);
		}
		
		Event.stop(e);
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
			var field = document.getElementsByClassName('AssetTableField')[0];
			field.setAttribute('href', updateURL);
			field.refresh();
		} catch(er) {
			errorMessage('Error searching');
		}

		return false;	
	}
}
