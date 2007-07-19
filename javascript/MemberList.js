/**
 * Auto-lookup on ajax fields
 */
AjaxMemberLookup = {
	initialise : function() {
		var div = document.createElement('div');
		div.id = this.id + '_ac';
		div.className = 'autocomplete';
		this.parentNode.appendChild(div);
		new Ajax.Autocompleter(this.id, div.id, 'admin/security/autocomplete/' + this.name, {
			afterUpdateElement : this.afterAutocomplete.bind(this)
		});
	},
	afterAutocomplete : function(field, selectedItem) {
		var data = selectedItem.getElementsByTagName('span')[1].innerHTML;
		var items = data.split(",");
		form = Element.ancestorOfType(field, 'form');
		
		form.elements.FirstName.value = items[0];
		form.elements.Surname.value = items[1];
		form.elements.Email.value = items[2];
		if(items[3])
			form.elements.Password.value = items[3];
		
		var fieldSet = field.parentNode.parentNode.getElementsByTagName('input');
		
		ajaxSubmitFieldSet('admin/security/savemember?MemberListBaseGroupID='.$('MemberListBaseGroupID'), fieldSet);
	}		
}

/**
 * Member list behaviour
 */
 
Behaviour.register({
	'#MemberList tr' : {
		onmouseover : hover_over,
		onmouseout : hover_out,

		onclick : function() {
			if(this.className.indexOf('addrow') == -1) {
				Element.addClassName(this, 'loading');
				new Ajax.Request('admin/security/getmember', {
					method : 'post', 
					postBody : 'ID=' + this.id.replace('member-','') + '&ajax=1',
					onSuccess : this.select_success.bind(this)
				});
				
			} else {
				if($('Form_MemberForm'))
					$('Form_MemberForm').innerHTML = "<p>Choose a member from above.</p>";
			}
		},
		select_success : function(response) {
			Element.removeClassName(this, 'loading');
			$('Form_MemberForm').loadNewPage(response.responseText);
	
			statusMessage('loaded','good');
			// for (var n in tinyMCE.instances) tinyMCE.removeMCEControl(n);
		}
	},

	'#MemberList thead tr' : {
		onmouseover : null,
		onmouseout : null,
		onclick : null
	},
	
	'#MemberList' : {
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
	},

	'#MemberList input' : AjaxMemberLookup,
	
	'#MemberList a.deletelink' : {
		onclick : function(event) {
			if(confirm("Do you want to remove this member from the group?")) {
				this.getElementsByTagName('img')[0].src = 'cms/images/network-save.gif';
				ajaxLink(this.href);
			}
			Event.stop(event);
			return false;
		}
	},
	
	'#MemberList tr.addrow' : {
		onmouseover : null,
		onmouseout : null,
		onclick : null
	},
	
	'#MemberList tr.addrow td.actions input' : {
		initialise: function() {
			data = this.parentNode.parentNode.getElementsByTagName('input');
			var i,item,error = [];
			for(i=0;item=data[i];i++) {
				item.originalSerialized = Form.Element.serialize(item);
			}
		},
		
		onclick : function(event) {
			data = this.parentNode.parentNode.getElementsByTagName('input');
			var i,item,error = [];
			for(i=0;item=data[i];i++) {
				if(item.name == 'Email' && !item.value) error[error.length] = "Email";
				if(item.name == 'Password' && !item.value) error[error.length] = "Password";
			}
			if(error.length > 0) {
				alert('Please enter a ' + error.join(' and a ') + ' to add a member.');
				
			} else {
				ajaxSubmitFieldSet('admin/security/addmember?MemberListBaseGroupID='.$('MemberListBaseGroupID') , data);
			}
			
			return false;
		}
	},
	
	'#Form_EditForm' : {
		changeDetection_fieldsToIgnore : {
			'MemberListBaseGroup' : true,
			'MemberListOrderByField' : true,
			'MemberListOrderByOrder' : true,
			'MemberListGroup' : true,
			'MemberListSearch' : true
		}
	}
});

MemberListFilterButton = Class.create();
MemberListFilterButton.applyTo('div.MemberListField #MemberListFilterButton');
MemberListFilterButton.prototype = {
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
	
	onclick: function(event) {
		
		var baseGroup = $('MemberListBaseGroupID').value;
		
		var updateURL = 'admin/security/listmembers/' + baseGroup + '?';
		
		for( var index = 0; index < this.inputFields.length; index++ ) {
			if( this.inputFields[index].tagName ) {
				updateURL += this.inputFields[index].name + '=' + encodeURIComponent( this.inputFields[index].value ) + '&';
			}
		}
		
		updateURL += 'ajax=1';
		
		new Ajax.Request( updateURL, {
			onSuccess: function( response ) {
				$('MemberList').innerHTML = response.responseText;
				// Behaviour.debug();
				Behaviour.apply( $('MemberList') );
			},
			onFailure: function( response ) {
				errorMessage('Could not filter results: ' + response.responseText );
			}
		});
		
		return false;	
	}
}

Behaviour.register({
	'div#MemberList div.PageControls a' : {
		onclick: function() {
			new Ajax.Request( this.href + '&ajax=1', {
				onSuccess: function( response ) {
					$('MemberList').innerHTML = response.responseText;
					// Behaviour.debug();
					Behaviour.apply( $('MemberList') );
				},
				onFailure: function( response ) {
					errorMessage('Could not filter results: ' + response.responseText );
				}
			});
			
			return false;
		}
	}
});

function reloadMemberList( groupID ) {
	
	if( !groupID )
		groupID = $('MemberListBaseGroupID').value;
		
	if($('MemberListStart')) var listStart = $('MemberListStart').value;
	else var listStart = 0;
	
	new Ajax.Request( 'admin/security/listmembers?&ajax=1&MemberListBaseGroup=' + groupID + '&MemberListStart=' + listStart, {
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

Behaviour.register({
	'.MemberListFilter a.showhide' : {
		initialise: function() {
			this.open = !Element.hasClassName( this, 'closed' );
		},
		
		onclick: function() {
			if( this.open )
				this.openControls();
			else
				this.closeControls();
			
			this.open = !this.open;
			
			return false;
		},
		
		openControls: function() {
			Element.removeClassName( this, 'closed' );
			$('MemberListFilterControls').style.display = 'block';
		},
		
		closeControls: function() {
			Element.removeClassName( this, 'closed' );
			$('MemberListFilterControls').style.display = 'none';
		}
	}
});