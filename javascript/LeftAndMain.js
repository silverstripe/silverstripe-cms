var _AJAX_LOADING = false;

Behaviour.register({

	'#MainMenu li' : {
		onclick : function(event) {
			return LeftAndMain_window_unload(); // Confirm if there are unsaved changes
			window.location.href = this.getElementsByTagName('a')[0].href;
			Event.stop(event);
		}
	},

	'#Menu-help' : {
		onclick : function() {
			var w = window.open(this.getElementsByTagName('a')[0].href, 'help');
			w.focus();
			return false;
		}
	},

	'#Logo' : {
		onclick : function() {
			var w = window.open(this.getElementsByTagName('a')[0].href);
			w.focus();
			return false;
		}
	},
	
	'#EditMemberProfile': {
		onclick: function(e) {
			var el = Event.element(e);
			GB_show('Edit Profile', el.attributes.href.value, 290, 500);
			Event.stop(e);
		}
	}

});

function isVisible(el) {
	// if(typeof el.isVisible != 'undefined') return el.isVisible;
	if(el.tagName == "body" || el.tagName == "BODY") return (el.isVisible = true);
	else if(el.style.display == 'none') return (el.isVisible = false);
	else return (el.isVisible = isVisible(el.parentNode));
}


LeftAndMain_window_unload = function() {
	window.exiting = true; // this is used by prototype
	if(typeof autoSave == 'function') {
		return autoSave(true);
	}
}

// Event.observe(window, 'beforeunload', LeftAndMain_window_unload);

/**
 * Unlock the locked status message.
 * Show a queued message, if one exists
 */
function unlockStatusMessage() {
	statusMessage.locked = false;
	if(statusMessage.queued) {
		statusMessage(
			statusMessage.queued.msg,
			statusMessage.queued.type,
			statusMessage.queued.showNetworkActivity);

		statusMessage.queued = null;
	}
}

/**
 * Move form actions to the top and make them ajax
 */
function ajaxActionsAtTop(formName, actionHolderName, tabName) {
	var actions = document.getElementsBySelector('#' + formName + ' .Actions')[0];
	var holder;

	if((holder = $(actionHolderName)) && holder != actions) {
		holder.parentNode.removeChild(holder);
	}

	if(actions) {
		actions.id = actionHolderName;
		actions.className = 'ajaxActions';

		$(tabName).appendChild(actions);
		prepareAjaxActions(actions, formName, tabName);
	}
}

/**
 * Prepare the ajax actions so that the buttons actually do something
 */
function prepareAjaxActions(actions, formName, tabName) {
	var i, button, list = actions.getElementsByTagName('input')
	for (i=0;button=list[i];i++) {
		button.ownerForm = $(formName);

		button.onclick = function(e) {
			if(!e) e = window.event;
			// tries to call a custom method of the format "action_<youraction>_right"
			if(window[this.name + '_' + tabName]) {
				window[this.name + '_' + tabName](e);
			} else {
				statusMessage('...');
				Ajax.SubmitForm(this.ownerForm, this.name, {
					onSuccess: Ajax.Evaluator,
					onFailure: ajaxErrorHandler
				});
			}
			return false;
		}
		// behaveAs(button, StatusTitle);
	}
}

/**
 * Submit the given form and evaluate the Ajax response.
 * Needs to be bound to an object with the following parameters to work:
 *  - form
 *  - action
 *  - verb
 *
 * The bound function can then be called, with the arguments passed
 */

function ajaxSubmitForm(automated, callAfter, form, action, verb) {
	var alreadySaved = false;
	if($(form).elements.length < 2) alreadySaved = true;

	if(alreadySaved) {
		if(callAfter) callAfter();

	} else {
		statusMessage(verb + '...', '', true);

		var success = function(response) {
			Ajax.Evaluator(response);
			if(callAfter) callAfter();
		}

		if(callAfter) success = success.bind({callAfter : callAfter});
		Ajax.SubmitForm(form, action, {
			onSuccess : success,
			onFailure : function(response) {
				errorMessage('Error ' + verb, response);
			}
		});
	}

	return false;
};

/**
 * Post the given fields to the given url
 */
function ajaxSubmitFieldSet(href, fieldSet, extraData) {
	// Build data
	var i,field,data = "ajax=1";
	for(i=0;field=fieldSet[i];i++) {
		data += '&' + Form.Element.serialize(field);
	}
	if(extraData){
		data += '&'+extraData;
	}
	// Send request
	new Ajax.Request(href, {
		method : 'post', postBody : data,
		onSuccess : function(response) {
			//alert(response.responseText);
			Ajax.Evaluator(response);
		},
		onFailure : function(response) {
			alert(response.responseText);
			//errorMessage('Error: ', response);
		}
	});
}

/**
 * Post the given fields to the given url
 */
function ajaxLink(href) {
	// Send request
	new Ajax.Request(href + (href.indexOf("?") == -1 ? "?" : "&") + "ajax=1", {
		method : 'get',
		onSuccess : Ajax.Evaluator,
		onFailure : ajaxErrorHandler
	});
}

/**
 * Load a URL into the given form
 */
function ajaxLoadPage() {
	statusMessage('loading...', 2, true);
	new Ajax.Request(this.URL + '&ajax=1', {
		method : 'get',
		onSuccess : ajaxLoadPage_success.bind(this)
	});
}
function ajaxLoadPage_success(response) {
	statusMessage('loaded');
	$(this.form).loadNewPage(response.responseText);
}

/**
 * Behaviour of the statuts message.
 */
Behaviour.register({
	'#statusMessage' : {
		showMessage : function(message, type, waitTime, clearManually) {
			if(this.fadeTimer) {
				clearTimeout(this.fadeTimer);
				this.fadeTimer = null;
			}
			if(this.currentEffect) {
				this.currentEffect.cancel();
				this.currentEffect = null;
			}

			this.innerHTML = message;
			this.className = type;
			Element.setOpacity(this, 1);

			//this.style.position = 'absolute';
			this.style.display = '';
			this.style.visibility = '';

			if(!clearManually) {
				this.fade(0.5,waitTime ? waitTime : 5);
			}
		},
		clearMessage : function(waitTime) {
			this.fade(0.5, waitTime);
		},
		fade: function(fadeTime, waitTime) {
			if(!fadeTime) fadeTime = 0.5;

			// Wait a bit before fading
			if(waitTime) {
				this.fadeTimer = setTimeout((function() {
					this.fade(fadeTime);
				}).bind(this), waitTime * 1000);

			// Fade straight away
			} else {
			 	this.currentEffect = new Effect.Opacity(this,
				    { duration: 0.5,
				      transition: Effect.Transitions.linear,
				      from: 1.0, to: 0.0,
				      afterFinish : this.afterFade.bind(this) });
			}
		},
		afterFade : function() {
			this.style.visibility = 'hidden';
			this.style.display = 'none';
			this.innerHTML = '';
		}
	}
});

/**
 * Show a status message.
 *
 * @param msg String
 * @param type String (optional) can be 'good' or 'bad'
 * @param clearManually boolean Don't automatically fade message.
 * @param container custom #statusMessage element to show message.
 */
function statusMessage(msg, type, clearManually, container) {
	var statusMessageEl = $('statusMessage');
	if(container != null) statusMessageEl = container; 
	if(statusMessageEl) {
		if(msg) {
			statusMessageEl.showMessage(msg, type, msg.length / 10, clearManually);
		} else {
			statusMessageEl.clearMessage();
		}
	}
}

function clearStatusMessage() {
	$('statusMessage').clearMessage();
}

/**
 * Called when something goes wrong
 */
function errorMessage(msg, fullMessage) {
	// Show complex error for developers in the console
	if(fullMessage) {
		// Get the message from an Ajax response object
		try {
			if(typeof fullMessage == 'object') fullMessage = fullMessage.status + '//' + fullMessage.responseText;
		} catch(er) {
			fullMessage = "";
		}
		console.error(fullMessage);
	}
	
	msg = msg.replace(/\n/g,'<br>');

	$('statusMessage').showMessage(msg,'bad');
}

function ajaxErrorHandler(response) {
	errorMessage('Server Error', response);
}

/**
 * Applying StatusTitle to an element will mean that the title attribute is shown as a statusmessage
 * upon hover
 */
/* Commenting out because on IE6, IE7, and Safari 3, the statusmessage becomes
 *  'null' on 2nd hover and because there is not room for long titles when
 *  action buttons are on the same line.
StatusTitle = Class.create();
StatusTitle.prototype = {
	onmouseover : function() {
		if(this.title) {
			this.message = this.title;
			this.title = null;
		}
		if(this.message) {
			$('statusMessage').showMessage(this.message);
		}
	},
	onmouseout : function() {
		if(this.message) {
			$('statusMessage').fade(0.3,1);
		}
	}
}
*/

/**
 * BaseForm is the base form class used in the CMS.
 */
BaseForm = Class.create();
BaseForm.prototype = {
	intitialize: function() {
		this.visible = this.style.display == 'none' ? false : true;

		// Collect all the buttons and attach handlers
		this.buttons = [];
		var i,input,allInputs = this.getElementsByTagName('input');
		for(i=0;input=allInputs[i];i++) {
			if(input.type == 'button' || input.type == 'submit') {
				this.buttons.push(input);
				input.holder = this;
				input.onclick = function() { return this.holder.buttonClicked(this); }
			}
		}
	},
	show: function() {
		this.visible = true;
		Element.hide(show);
	},
	hide: function() {
		this.visible = false;
		Element.hide(this);
	},
	isVisible: function() {
		return this.visible;
	},
	buttonClicked: function(button) {
		return true;
	}
}


/**
 * ChangeTracker is a class that can be applied to forms to support change tracking on forms.
 */
ChangeTracker = Class.create();
ChangeTracker.prototype = {
	initialize: function() {
		this.resetElements();
	},

	/**
	 * Reset all the 'changed field' data.
	 */
	resetElements: function(debug) {
    var elements = Form.getElements(this);
		var i, element;
		for(i=0;element=elements[i];i++) {
			// Initialise each element
			if(element.resetChanged) {
				element.resetChanged();
			} else {
				element.originalSerialized = Form.Element.serialize(element);
			}
		}
	},

	field_changed: function() {
		// Something a value will go from 'undefined' to ''.  Ignore such changes
		if((this.originalSerialized+'') == 'undefined') return Form.Element.serialize(this) ? true : false;
		else return this.originalSerialized != Form.Element.serialize(this);
	},

	/**
	 * Returns true if something in the form has been changed
	 */
	isChanged: function() {
    	var elements = Form.getElements(this);
		var i, element;
		for(i=0;element=elements[i];i++) {
		    // NOTE: TinyMCE coupling
		    // Ignore mce-generated elements
		    if(element.className.substr(0,3) == 'mce') continue;
		    
			if(!element.isChanged) element.isChanged = this.field_changed;
			if(!this.changeDetection_fieldsToIgnore[element.name] && element.isChanged()) {
				//console.log('Changed:'+ element.id + '(' + this.originalSerialized +')->('+Form.Element.serialize(element)+')' );
				//console.log(element)

				return true;
			}
		}
		return false;
	},

	changeDetection_fieldsToIgnore : {
		'Sort' : true
	},

	/**
	 * Serialize only the fields to change.
	 * You can specify the names of fields that must be included as arguments
	 */
	serializeChangedFields: function() {
    var elements = Form.getElements(this);
    var queryComponent, queryComponents = new Array();
		var i, element;

		var forceFields = {};
		if(arguments) {for(var i=0;i<arguments.length;i++) forceFields[arguments[i]] = true;}

		for(i=0;element=elements[i];i++) {
			if(!element.name.match(/^action_(.+)$/i)) // For dropdown those 'action_xxx' fields.
			{	if(!element.isChanged) element.isChanged = this.field_changed;
				if(forceFields[element.name] || (element.isChanged()) || element.name.match(/\[.*\]/g) ) {
	      		queryComponent = Form.Element.serialize(element);
			    if (queryComponent)
					queryComponents.push(queryComponent);
			    } else {
			    	// Used by the Sapphire code to preserve the form field value

			    	if( element.name.match( '/\]$/' ) )
			    		queryComponents.push(element.name.substring( 0, element.name.length - 1 ) + '_unchanged' + ']=1' );
			    	else
			    		queryComponents.push(element.name + '_unchanged=1');
			    }
			}
	  }
		//alert(queryComponents.join('&'));
    return queryComponents.join('&');
	},

	/**
	 * Serialize all the fields on the page
	 */
	serializeAllFields: function() {
		return Form.serializeWithoutButtons(this);
	}
}

function hideLoading() {
	if($('Loading')) $('Loading').style.display = 'none';
	Element.removeClassName(document.body, 'stillLoading');
}
function baseHref() {
	var baseTags = document.getElementsByTagName('base');
	if(baseTags) return baseTags[0].href;
	else return "";
}

returnFalse = function() {
	return false;
}
showResponseAsSuccessMessage = function(response) {
	statusMessage(response.responseText, 'good');
}

/**
 * This function is called by prototype when it receives notification that the user was logged out.
 * It redirects back to the login form.
 */
function onSessionLost() {
	w = window.open('Security/login');
	if(w) {
	    alert("Please log in and then try again");
	} else {
	    alert("Please enable pop-ups for this site");
	}
}

var _CURRENT_CONTEXT_MENU = null;

/**
 * Create a new context menu
 * @param event The event object
 * @param owner The DOM element that this context-menu was requested from
 * @param menuItems A map of title -> method; context-menu operations to get called
 */
function createContextMenu(event, owner, menuItems) {
	if(_CURRENT_CONTEXT_MENU) {
		document.body.removeChild(_CURRENT_CONTEXT_MENU);
		_CURRENT_CONTEXT_MENU = null;
	}

	var menu = document.createElement("ul");
	menu.className = 'contextMenu';
	menu.style.position = 'absolute';
	menu.style.left = event.clientX + 'px';
	menu.style.top = event.clientY + 'px';

	var menuItemName, menuItemTag, menuATag;
	for(menuItemName in menuItems) {
		menuItemTag = document.createElement("li");

		menuATag = document.createElement("a");
		menuATag.href = "#";
		menuATag.onclick = menuATag.oncontextmenu = contextmenu_onclick;
		menuATag.innerHTML = menuItemName;
		menuATag.handler = menuItems[menuItemName];
		menuATag.owner = owner;

		menuItemTag.appendChild(menuATag);
		menu.appendChild(menuItemTag);
	}

	document.body.appendChild(menu);

	document.body.onclick = contextmenu_close;

	_CURRENT_CONTEXT_MENU = menu;

	return menu;
}

function contextmenu_close() {
	if(_CURRENT_CONTEXT_MENU) {
		document.body.removeChild(_CURRENT_CONTEXT_MENU);
		_CURRENT_CONTEXT_MENU = null;
	}
}

function contextmenu_onclick() {
	this.handler(this.owner);
	contextmenu_close();
	return false;
}

/**
 * Shows an ajax loading indicator.
 *
 * @param id String Identifier for the newly created image
 * @param container ID/DOM Element
 * @param imgSrc String (optional)
 * @param insertionType Object (optional) Prototype-style insertion-classes, defaults to Insertion.Bottom
 * @param displayType String (optional) "inline" or "block"
 */
function showIndicator(id, container, imgSrc, insertionType, displayType) {
	if(!id || !$(container)) return false;
	if(!imgSrc) imgSrc = "cms/images/network-save.gif";
	if(!displayType) displayType = "inline";
	if(!insertionType) insertionType = Insertion.Bottom;

	if(!$(id)) {
		var html = '<img src="' + imgSrc + '" class="indicator ' + displayType + '" id="' + id + '" style="display: none" />';
		new insertionType(container, html);
	}

	Effect.Appear(id);
}

function hideIndicator(id) {
	Effect.Fade(id, {duration: 0.3});
}

setInterval(function() {
		new Ajax.Request("Security/ping");
}, 180*1000);

/**
 * Find and enable TinyMCE on all htmleditor fields
 * Pulled in from old tinymce.template.js
 */

function nullConverter(url) {
	return url;
}

Behaviour.register({
    'textarea.htmleditor' : {
        initialize : function() {
            tinyMCE.execCommand("mceAddControl", true, this.id);
            this.isChanged = function() {
                return tinyMCE.getInstanceById(this.id).isDirty();
            }
            this.resetChanged = function() {
                inst = tinyMCE.getInstanceById(this.id);
                if (inst) inst.startContent = tinymce.trim(inst.getContent({format : 'raw', no_events : 1}));
            }
        }
    }
});
