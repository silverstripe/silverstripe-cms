CommentTableField = Class.create();
CommentTableField.prototype = {
	initialize: function() {
		var rules = {};
		
		rules['#'+this.id+' table.data a.spamlink'] = {
			onclick: this.removeRowAfterAjax.bind(this)
		};
		
		rules['#'+this.id+' table.data a.approvelink'] = {
			onclick: this.removeRowAfterAjax.bind(this)
		};
		
		rules['#'+this.id+' table.data a.hamlink'] = {
			onclick: this.removeRowAfterAjax.bind(this)
		};
		rules['#Form_EditForm div.CommentFilter input'] = {
				onkeypress : this.prepareSearch.bind(this)
		};
		
		rules['#Form_EditForm'] = {
			changeDetection_fieldsToIgnore : {
				'ctf[start]' : true,
				'ctf[ID]' : true,
				'CommentFilterButton' : true,
				'CommentFieldName' : true,
				'Name' : true,
				'Comment' : true,
				'Comments[]' : true,
				'Page' : true,
				'CommentSearch' : true
			}
		}
		
		Behaviour.register(rules);
	},
	
	removeRowAfterAjax: function(e) {
		var img = Event.element(e);
		var link = Event.findElement(e,"a");
		var row = Event.findElement(e,"tr");
		
		img.setAttribute("src",'cms/images/network-save.gif'); // TODO doesn't work in Firefox1.5+
		new Ajax.Request(
			link.getAttribute("href"),
			{
				method: 'post', 
				postBody: 'forceajax=1',
				onComplete: function(){
					Effect.Fade(row);
				}.bind(this),
				onFailure: ajaxErrorHandler
			}
		);
		Event.stop(e);
	},
	
	// prevent submission of wrong form-button (CommentFilterButton)
	prepareSearch: function(e) {
		// IE6 doesnt send an event-object with onkeypress
		var event = (e) ? e : window.event;
		var keyCode = (event.keyCode) ? event.keyCode : event.which;
		
		if(keyCode == Event.KEY_RETURN) {
			var el = Event.element(event);
			$('CommentFilterButton').onclick(event);
			Event.stop(event);
			return false;
		}
	}
}

CommentTableField.applyTo('div.CommentTableField');

CommentFilterButton = Class.create();
CommentFilterButton.applyTo('#CommentFilterButton');
CommentFilterButton.prototype = {
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
	    try {
    	    var form = Event.findElement(e,"form");
    	    var fieldName = $('CommentFieldName').value;
    	    var fieldID = form.id + '_' + fieldName;

    		var updateURL = form.action + '/field/' + fieldName + '?ajax=1';
    		for( var index = 0; index < this.inputFields.length; index++ ) {
    			if( this.inputFields[index].tagName ) {
    				updateURL += '&' + this.inputFields[index].name + '=' + encodeURIComponent( this.inputFields[index].value );
    			}
    		}
    		updateURL += ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '');

    		new Ajax.Updater( fieldID, updateURL, {
    			onComplete: function() {
    			    Behaviour.apply($(fieldID), true);
    			},
    			onFailure: function( response ) {
    				errorMessage('Could not filter results: ' + response.responseText );
    			}.bind(this)
    		});
		} catch(er) {
			errorMessage('Error searching');
		}
		
		return false;	
	}
}