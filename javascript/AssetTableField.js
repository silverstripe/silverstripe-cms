(function($) {
	$.concrete('ss', function($){
		
		$('.AssetTableField :checkbox').concrete({
			onchange: function() {
				var container = this.parents('.AssetTableField');
				var input = container.find('input#deletemarked');
				if(container.find(':input[name=Files\[\]]:checked').length) {
					input.removeAttr('disabled');
				} else {
					input.attr('disabled', 'disabled');
				}
			}
		})
		
		/**
		 * Batch delete files marked by checkboxes in the table.
		 * Refreshes the form field afterwards via ajax.
		 */
		$('.AssetTableField input#deletemarked').concrete({
			onmatch: function() {
				this.attr('disabled', 'disabled');
				this._super();
			},
			onclick: function(e) {
				if(!confirm(ss.i18n._t('AssetTableField.REALLYDELETE'))) return false;
				
				var container = this.parents('.AssetTableField');
				var self = this;
				this.addClass('loading');
				$.post(
					container.attr('href') + '/deletemarked',
					this.parents('form').serialize(),
					function(data, status) {
						self.removeClass('loading');
						container[0].refresh();
					}
				);
				return false;
			}
		});
	});
}(jQuery));

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
					'FileSearch' : true,
					'Files[]' : true
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