var _AJAX_LOADING = false;

// Resize the tabs once the document is properly loaded
// @todo most of this file needs to be tidied up using jQuery
if(typeof(jQuery) != 'undefined') {
	(function($) {
		$(document).ready(function() {
			window.onresize(true);
		});

		//Turn off autocomplete to fix the access tab randomly switching radio buttons in Firefox when refresh the page
		// with an anchor tag in the URL. E.g: /admin#Root_Access
		//Autocomplete in the CMS also causes strangeness in other browsers, so this turns it off for all browsers.
		//see the following for demo and explanation of the Firefox bug:
		//  http://www.ryancramer.com/journal/entries/radio_buttons_firefox/
		$("#Form_EditForm").attr("autocomplete", "off");
	})(jQuery);
}

/**
 * Code for the separator bar between the two panes
 */
function DraggableSeparator() {
	this.onmousedown = this.onmousedown.bindAsEventListener(this);
	// this.onselectstart = this.onselectstart.bindAsEventListener(this);
}
DraggableSeparator.prototype = {
	onmousedown : function(event) {
		this.leftBase = $('left').offsetWidth - Event.pointerX(event);
		this.separatorBase = getDimension($('separator'),'left') - Event.pointerX(event);
		this.rightBase = getDimension($('right'),'left') - Event.pointerX(event);

		document.onmousemove = this.document_mousemove.bindAsEventListener(this);
		document.onmouseup = this.document_mouseup.bindAsEventListener(this);

		// MozUserSelect='none' prevents text-selection during drag, in firefox.
		Element.setStyle($('right'), {MozUserSelect: 'none'});
		Element.setStyle($('left'), {MozUserSelect: 'none'});
		// onselectstart captured to prevent text-selection in IE
		document.body.onselectstart = this.body_selectstart.bindAsEventListener(this);
	},
	document_mousemove : function(event) {
		$('left').style.width = (this.leftBase + Event.pointerX(event)) + 'px';
		fixRightWidth();
	},
	document_mouseup : function(e) {
		// MozUserSelect='' re-enables text-selection in firefox.
		Element.setStyle($('right'), {MozUserSelect: ''});
		Element.setStyle($('left'), {MozUserSelect: ''});
		document.onmousemove = null;
	},

	body_selectstart : function(event) {
		Event.stop(event);
		return false;
	}
}

function fixRightWidth() {
	if(!$('right')) return;

	// Absolutely position all the elements
	var sep = getDimension($('left'),'width') + getDimension($('left'),'left');
	$('separator').style.left = (sep + 2) + 'px';
	$('right').style.left = (sep + 6) + 'px';

	// Give the remaining space to right
	var bodyWidth = parseInt(document.body.offsetWidth);
	var leftWidth = parseInt($('left').offsetWidth);
	var sepWidth = parseInt($('separator').offsetWidth - 8);
	var rightWidth = bodyWidth - leftWidth - sepWidth -18;
	
	// Extra pane in right for insert image/flash/link things
	if($('contentPanel') && $('contentPanel').style.display != "none") {
		rightWidth -= 210;
		$('contentPanel').style.left = leftWidth + sepWidth + rightWidth + sepWidth + 23 + 'px';
	}

	if(rightWidth >= 0) $('right').style.width = rightWidth + 'px';
}

Behaviour.register({
	'#separator' : DraggableSeparator,

	'#left' : {
		hide : function() {
			if(!this.hidden) {
				this.hidden = true;
				this.style.width = null;
				Element.addClassName(this,'hidden');
				Element.addClassName('separator','hidden');
				fixRightWidth();
			}
		},
		show : function() {
			if(this.hidden) {
				this.hidden = false;
				Element.removeClassName(this,'hidden');
				Element.removeClassName('separator','hidden');
				fixRightWidth();
			}
		}
	},

	'#MainMenu li' : {
		onclick : function(event) {
			return LeftAndMain_window_unload(); // Confirm if there are unsaved changes
			window.location.href = this.getElementsByTagName('a')[0].href;
			Event.stop(event);
		}
	},

	'#Menu-Help' : {
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



window.ontabschanged = function() {
	var formEl = $('Form_EditForm');
	if( !formEl ) formEl = $('Form_AddForm');

	if( !formEl )
		return;

	var fs = formEl.getElementsByTagName('fieldset')[0];
	if(fs && fs.parentNode == formEl) fs.style.height = formEl.style.height;

	// var divs = document.getElementsBySelector('#Form_EditForm div');
	/*for(i=0;i<divs.length;i++) {
		if( ( Element.hasClassName(divs[i],'tab') || Element.hasClassName(divs[i],'tabset') ) && isVisible(divs[i]) ) {
			if(navigator.appName == "Microsoft Internet Explorer")
				fitToParent(divs[i], i == 0 ? 18 : 0);
			else
				fitToParent(divs[i], 3);
		}
	}*/
	
	if(typeof  _TAB_DIVS_ON_PAGE != 'undefined') {
		for(i = 0; i < _TAB_DIVS_ON_PAGE.length; i++ ) {
			fitToParent(_TAB_DIVS_ON_PAGE[i], 30);
		}
	}
	
	// Non-tab alternative
	if($('ScrollPanel')) {
		fitToParent('ScrollPanel', 0);
	}
}

window.onresize = function(init) {
	var right = $('right');

	if(typeof fitToParent == 'function') {
		fitToParent('right', 12);
		if($('ModelAdminPanel')) {
			fitToParent('ModelAdminPanel',-60);
		}
		if($('contentPanel')) {
			fitToParent('contentPanel', 12);
		}
	}

	if( $('left') && $('separator') && right ) {
		// #right has padding-bottom to make room for AJAX Action buttons so we need to add that
 		if (navigator.appName == "Microsoft Internet Explorer") {
			var paddingBottomOffset = 35;
		} else {
			var paddingBottomOffset = 20;
		}
		var rightH = parseInt(right.style.height) + paddingBottomOffset;
		$('left').style.height = $('separator').style.height = rightH + 'px';
	}

	if(typeof fitToParent == 'function') {
		if($('Form_EditForm')) fitToParent('Form_EditForm', 4);
		if($('Form_AddForm')) fitToParent('Form_AddForm', 4);
		
		if($('Form_EditorToolbarImageForm') && $('Form_EditorToolbarImageForm').style.display == "block") {
			fitToParent('Form_EditorToolbarImageForm', 5);
			fitToParent($('Form_EditorToolbarImageForm').getElementsByTagName('fieldset')[0]);
			if(navigator.appName == "Microsoft Internet Explorer") {
				fitToParent('Image');
			} else {
				fitToParent('Image', 250);
			}
		}
		if($('Form_EditorToolbarFlashForm') && $('Form_EditorToolbarFlashForm').style.display == "block") {
			fitToParent('Form_EditorToolbarFlashForm', 5);
			fitToParent($('Form_EditorToolbarFlashForm').getElementsByTagName('fieldset')[0]);
			if(navigator.appName == "Microsoft Internet Explorer") {
				fitToParent('Flash');
			} else {
				fitToParent('Flash', 130);
			}
		}
	
	}
	if(typeof fixHeight_left == 'function') fixHeight_left();
	if(typeof fixRightWidth == 'function') fixRightWidth();

	window.ontabschanged();
}

appendLoader(function() {
	// Only execute this code if it's actually called from the LeftAndMain interface
	// JSMin/concatenation can get this file included in strange places
	if(document.getElementById('left') && document.getElementById('right')) {
		document.body.style.overflow = 'hidden';
		window.onresize(true);
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
	var holder = $(actionHolderName);

	if ( holder && holder != actions ) {
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
		onFailure : ajaxErrorHandler
	});
}

/**
 * Load a URL into the given form
 */
function ajaxLoadPage() {
	statusMessage(ss.i18n._t('LOADING', 'loading...'),2,true);
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
		    // Ignore mce-generated elements and elements without a name
		    if(element.className.substr(0,3) == 'mce') continue;
			if(!element.name) continue;
		    
			if(!element.isChanged) element.isChanged = this.field_changed;
			if(!this.changeDetection_fieldsToIgnore[element.name] && element.isChanged()) {
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

/*
 * ModalForm provides a form with the functionality to prevent certian actions from occurring until
 * it's been closed.
 *
 * To use, You should run the blockEvent method as many times as needed.
 */
ModalForm = Class.extend('BaseForm');
ModalForm.prototype = {
	/*
	 * Prevent the given event from occurring on the given event while the form is open.
	 * These must be CMS-created events, not built-in javascript events.
	 * For example, form.blockEvent($('sitetree'), 'SelectionChanged')
	 */
	blockEvent: function(element, event) {
		element.observeMethod(event, (function() { return !this.isVisible();}).bind(this) );
	}
}


function doYouWantToRollback(handlers) {
	var url = document.getElementsByTagName('base')[0].href + 'admin/canceldraftchangesdialog';
	OpenModalDialog(url, handlers, 'Are you sure?' );
}

function modalDialog(url, handlers) {
	var baseURL = document.getElementsByTagName('base')[0].href;
	if(window.showModalDialog) {
		var result = showModalDialog(baseURL + url + '&Modal=1', null,  "status:no;dialogWidth:400px;dialogHeight:150px;edge:sunken");
		if(handlers[result])
			handlers[result]();

	}
}


ModalDialog = Class.create();
ModalDialog.prototype = {
	initialize: function(url, handlers) {
		this.url = url;
		this.handlers = handlers;
		this.timer = setInterval(this.interval.bind(this), 50);
		this.window = window.open(this.url, 'dialog', "status=no,width=400,height=150,edge=sunken");
		this.window.dialogObject = this;
		this.window.linkedObject = this;
		setTimeout( (function(){this.window.linkedObject = this;}).bind(this), 500);
	},
	force: function (val) {
		this.finished = true;
		this.clearInterval(this.time);

		if(this.handlers[val]) {
			_DO_YOU_WANT_TO_SAVE_IS_OPEN = false;
			(this.handlers[val])();
		} else {
			throw("Couldn't find a handler called '" + this.result + "'");
		}
	},
	interval: function() {
		if(this.finished) {
			clearInterval(this.timer);
			return;
		}
		if(!this.window || this.window.closed) {
			clearInterval(this.timer);
			if(this.handlers) {
				if(this.handlers[this.result]) {
					_DO_YOU_WANT_TO_SAVE_IS_OPEN = false;
					(this.handlers[this.result])();

				} else {
					throw("Couldn't find a handler called '" + this.result + "'");
				}
			}
		} else {
			this.window.focus();
		}
	}
}

window.top._OPEN_DIALOG = null;

OpenModalDialog = function( url, handlers, message ) {
	var dialog = new GBModalDialog( url, handlers, message );
}

GBModalDialog = Class.create();
GBModalDialog.prototype = {

	initialize: function( url, handlers, message ) {
		this.url = url;
		this.handlers = handlers;
		this.caption = message;

		window.top._OPEN_DIALOG = this;

		GB_show( this.caption, this.url, 110, 450 );
	},

	execHandler: function( handler ) {
		GB_hide();

		if( this.handlers[handler] )
			this.handlers[handler]();
		else
			throw( "Unknown handler '" + handler + "'" );
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
            if(typeof tinyMCE != 'undefined'){
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
    }
})
