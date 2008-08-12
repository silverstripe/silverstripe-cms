/**
 * Modified 2006-10-05, Ingo Schommer
 * This is more or less a copy of Member.js, with additions and changes
 * to match the switch from Member.php to MemberTableField.php all over the UI.
 * Eventually it will replace Member.js (please remove this message then).
 */
 
// no confirm message for removal from a group
ComplexTableField.prototype.deleteConfirmMessage = null;

/**
 * Auto-lookup on ajax fields
 */
AjaxMemberLookup = {
	initialise : function() {
		var div = document.createElement('div');
		div.id = this.id + '_ac';
		div.className = 'autocomplete';
		this.parentNode.appendChild(div);
		if(this.id) {
			new Ajax.Autocompleter(this.id, div.id, 'admin/security/autocomplete/' + this.name, {
				afterUpdateElement : this.afterAutocomplete.bind(this)
			});
			
		}
	},
	afterAutocomplete : function(field, selectedItem) {
		var data = selectedItem.getElementsByTagName('span')[1].innerHTML;
		var items = data.split(",");
		form = Element.ancestorOfType(field, 'form');
		// TODO more flexible column-detection
		form.elements.FirstName.value = items[0];
		form.elements.Surname.value = items[1];
		form.elements.Email.value = items[2];
		if(items[3] && form.elements.Password)
			form.elements.Password.value = items[3];
		
		//var fieldSet = field.parentNode.parentNode.getElementsByTagName('input');
		//ajaxSubmitFieldSet('admin/security/savemember?MemberBaseGroupID='.$('MemberBaseGroupID'), fieldSet);
	}		
}

MemberTableField = Class.create();
MemberTableField.applyTo('#Form_EditForm div.MemberTableField');
MemberTableField.prototype = {
	
	initialize: function() {
		Behaviour.register('MemberTableField',{
			'#Form_EditForm div.MemberFilter input' : {
				onkeypress : this.prepareSearch.bind(this)
			},

			'#Form_EditForm div.MemberTableField table.data tr.addtogrouprow input' : {
				onkeypress : this.prepareAddToGroup.bind(this)
			},

			'#Form_EditForm div.MemberTableField table.data tr.addtogrouprow #Form_AddRecordForm_action_addtogroup' : {
				onclick : this.prepareAddToGroup.bind(this)
			},

			'#Form_EditForm div.MemberTableField table.data tr.addtogrouprow td.actions input' : {
				initialise: function() {
					data = this.parentNode.parentNode.getElementsByTagName('input');
					var i,item,error = [];
					for(i=0;item=data[i];i++) {
						item.originalSerialized = Form.Element.serialize(item);
					}
				},
				onclick : this.addToGroup.bind(this)
			},
			
			//'#Form_EditForm div.MemberTableField input' : AjaxMemberLookup,
			
			'#Form_EditForm' : {
				changeDetection_fieldsToIgnore : {
					'ctf[start]' : true,
					'ctf[ID]' : true,
					'MemberOrderByField' : true,
					'MemberOrderByOrder' : true,
					'MemberGroup' : true,
					'MemberFilterButton' : true,
					'MemberFieldName' : true,
					'MemberDontShowPassword' : true,
					'MemberSearch' : true
				}
			}
		});
	},
	
	// prevent submission of wrong form-button (MemberFilterButton)
	prepareAddToGroup: function(e) {
		// IE6 doesnt send an event-object with onkeypress
		var event = (e) ? e : window.event;
		var keyCode = (event.keyCode) ? event.keyCode : event.which;
		if(keyCode == Event.KEY_RETURN) {
			var el = Event.element(event);
			this.addToGroup(event);
			Event.stop(event);
			return false;
		}
	},

	// prevent submission of wrong form-button (MemberFilterButton)
	prepareSearch: function(e) {
		// IE6 doesnt send an event-object with onkeypress
		var event = (e) ? e : window.event;
		var keyCode = (event.keyCode) ? event.keyCode : event.which;
		
		if(keyCode == Event.KEY_RETURN) {
			var el = Event.element(event);
			$('MemberFilterButton').onclick(event);
			Event.stop(event);
			return false;
		}
	},
	
	addToGroup: function(e) {
		// only submit parts of the form
		var data = this.parentNode.parentNode.getElementsByTagName('input');
		var i,item,error = [];
		var form = Event.findElement(e,"form");
		
		for(i=0;item=data[i];i++) {
			if(item.name == 'Email' && !item.value) error[error.length] = "Email";
			if(item.name == 'Password' && !item.value) error[error.length] = "Password";
		}
		
		if(error.length > 0) {
			alert('Please enter a ' + error.join(' and a ') + ' to add a member.');
		} else {
			updateURL = "";
			updateURL += Event.findElement(e,"form").action;
			// we can't set "fieldName" as a HiddenField because there might be multiple ComplexTableFields in a single EditForm-container
			updateURL += "&fieldName="+$('MemberFieldName').value;
			updateURL += "&action_callfieldmethod&&methodName=addtogroup&";

			ajaxSubmitFieldSet(updateURL, data);
		}
		
		return false;
	}
	
	/*
		initialise : function() {
			this.headerMap = [];
			
			var i, item, headers = this.getElementsByTagName('thead')[0].getElementsByTagName('tr')[0].getElementsByTagName('td');
			for(i=0;item=headers[i];i++) {
				this.headerMap[i] = item.className;
			}
		},
		
		setRecordDetails : function(id, details, groupID) {
			var row = document.getElementById('member-' + id);
			if(row) {
				var i, item, cells = row.getElementsByTagName('td');
				for(i=0;item=cells[i];i++) {
					if(details[this.headerMap[i]]) {
						item.innerHTML = details[this.headerMap[i]];
					}
				}
			} else {
				this.createRecord(id, details, groupID);
			}
		},
		createRecord : function (id, details, groupId) {
			var row = document.createElement('tr');
			row.id = 'member-' + id;
			var i, cell, cellField;
			for(i=0;cellField=this.headerMap[i];i++) {
				cell = document.createElement('td')
				if(details[cellField]) {
					cell.innerHTML = details[cellField];
				}
				row.appendChild(cell);
			}
			
			// Add the delete icon
			if(typeof groupId == 'undefined')
				var groupId = $('Form_EditForm').elements.ID.value;
			cell = document.createElement('td')
			cell.innerHTML = '<a class="deletelink" href="admin/security/removememberfromgroup/' + groupId + '/' + id + '"><img src="cms/images/delete.gif" alt="delete" /></a>';
			cell.getElementsByTagName('0');
			row.appendChild(cell);
			
			var tbody = this.getElementsByTagName('tbody')[0];
			var addRow = document.getElementsByClassName('addrow',tbody)[0];
			if(addRow) tbody.insertBefore(row, addRow);
			else tbody.appendChild(row);
			Behaviour.apply(row, true);
		},
		clearAddForm : function() {
			var tbody = this.getElementsByTagName('tbody')[0];
			var addRow = document.getElementsByClassName('addrow',tbody)[0];
			if(addRow) {
				var i,field,fields = addRow.getElementsByTagName('input');
				for(i=0;field=fields[i];i++) {
					if(field.type != 'hidden' && field.type != 'submit') field.value = '';
				}
			}
		},
		removeMember : function(memberID) {
			var record;
			if(record = $('member-' + memberID)) {
				record.parentNode.removeChild(record);
			} 
		}
		*/
}

MemberFilterButton = Class.create();
MemberFilterButton.applyTo('#Form_EditForm #MemberFilterButton');
MemberFilterButton.prototype = {
	initialize: function() {
		this.inputFields = new Array();
		
		var childNodes = this.parentNode.getElementsByTagName('input');
		
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
		if(!$('ctf-ID') || !$('MemberFieldName')) {
			return false;
		}
		
		var updateURL = "";
		updateURL += Event.findElement(e,"form").action;
		// we can't set "fieldName" as a HiddenField because there might be multiple ComplexTableFields in a single EditForm-container
		updateURL += "&fieldName="+$('MemberFieldName').value;
		updateURL += "&action_callfieldmethod&&methodName=ajax_refresh&";
		for( var index = 0; index < this.inputFields.length; index++ ) {
			if( this.inputFields[index].tagName ) {
				updateURL += this.inputFields[index].name + '=' + encodeURIComponent( this.inputFields[index].value ) + '&';
			}
		}
		updateURL += 'ajax=1' + ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '');

		new Ajax.Request( updateURL, {
			onSuccess: Ajax.Evaluator,
			onFailure: function( response ) {
				errorMessage('Could not filter results: ' + response.responseText );
			}.bind(this)
		});
		
		return false;	
	}
}

// has to be external from initialize() because otherwise request will double on each reload - WTF
Behaviour.register({
	'#Form_EditForm div.MemberTableField table.data input' : AjaxMemberLookup
});

/*
function reloadMemberTableField( groupID ) {
	
	if( !groupID )
		groupID = $('MemberBaseGroupID').value;
		
	if($('MemberStart')) var listStart = $('MemberStart').value;
	else var listStart = 0;
	
	new Ajax.Request( 'admin/security/listmembers?&ajax=1&MemberBaseGroup=' + groupID + '&MemberStart=' + listStart, {
		onSuccess: function( response ) {
			$('MemberList').innerHTML = response.responseText;
			// Behaviour.debug();
			Behaviour.apply( $('MemberList') );
		},
		onFailure: function( response ) {
			errorMessage('Could not filter results: ' + response.responseText );
		}
	});
}
*/