(function($) {

	/**
	 * Available Custom Events:
	 * <ul>
	 * <li>ajaxsubmit</li>
	 * <li>validate</li>
	 * <li>loadnewpage</li>
	 * 
	 * @class Main LeftAndMain interface with some control
	 * panel and an edit form.
	 * @name ss.LeftAndMain
	 */
	$('.LeftAndMain').concrete('ss', function($){
		return/** @lends ss.EditMemberProfile */ {
			/**
			 * @type Number Interval in which /Security/ping will be checked for a valid login session.
			 */
			PingIntervalSeconds: 5*60,
		
			onmatch: function() {
				var self = this;
			
				this._setupPinging();
				this._setupButtons();
				this._resizeChildren();
			
				// artificially delay the resize event 200ms
				// to avoid overlapping height changes in different onresize() methods
				$(window).resize(function () {
					var timerID = "timerLeftAndMainResize";
					if (window[timerID]) clearTimeout(window[timerID]);
					window[timerID] = setTimeout(function() {self._resizeChildren();}, 200);
				});
			
				// trigger resize whenever new tabs are shown
				// @todo This is called multiple times when tabs are loaded
				this.find('.ss-tabset').bind('tabsshow', function() {self._resizeChildren();});
			
				$('#Form_EditForm').bind('loadnewpage', function() {self._resizeChildren();});

				this._super();
			},

			/**
			 * This function is called by prototype when it receives notification that the user was logged out.
			 * It uses /Security/ping for this purpose, which should return '1' if a valid user session exists.
			 * It redirects back to the login form if the URL is either unreachable, or returns '0'.
			 */
			_setupPinging: function() {
				var onSessionLost = function(response, type, XMLHttpRequest) {
					if(XMLHttpRequest.status > 400 || response == 0) {
						// TODO will pile up additional alerts when left unattended
						if(window.open('Security/login')) {
						    alert("Please log in and then try again");
						} else {
						    alert("Please enable pop-ups for this site");
						}
					}
				};
			
				// setup pinging for login expiry
				setInterval(function() {
					jQuery.ajax({
						url: "Security/ping",
						global: false,
						error: onSessionLost,
						success: onSessionLost
					});
				}, this.PingIntervalSeconds() * 1000);
			},
		
			/**
			 * Make all buttons "hoverable" with jQuery theming.
			 */
			_setupButtons: function() {
				// Initialize buttons
				this.find(':submit, button, :reset').livequery(function() {
					jQuery(this).addClass(
						'ui-state-default ' +
						'ui-corner-all'
					)
					.hover(
						function() {
							$(this).addClass('ui-state-hover');
						},
						function() {
							$(this).removeClass('ui-state-hover');
						}
					)
					.focus(function() {
						$(this).addClass('ui-state-focus');
					})
					.blur(function() {
						$(this).removeClass('ui-state-focus');
					});
				});
			},
		
			/**
			 * Resize elements in center panel
			 * to fit the boundary box provided by the layout manager
			 */
			_resizeChildren: function() {
				$('#Form_EditForm').fitHeightToParent();
				$('#Form_EditForm fieldset', this).fitHeightToParent();
				// Order of resizing is important: Outer to inner
				// TODO Only supports two levels of tabs at the moment
				$('#Form_EditForm fieldset > .ss-tabset', this).fitHeightToParent();
				$('#Form_EditForm fieldset > .ss-tabset > .tab', this).fitHeightToParent();
				$('#Form_EditForm fieldset > .ss-tabset > .tab > .ss-tabset', this).fitHeightToParent();
				$('#Form_EditForm fieldset > .ss-tabset > .tab > .ss-tabset > .tab', this).fitHeightToParent();
			}
		};
	});
	
	/**
	 * @class Container for tree actions like "create", "search", etc.
	 * @name ss.TreeActions
	 */
	$('#TreeActions').concrete('ss', function($){
		return/** @lends ss.TreeActions */{
			
			/**
			 * Setup "create", "search", "batch actions" layers above tree.
			 * All tab contents are closed by default.
			 */
			onmatch: function() {
				this.tabs({
					collapsible: true,
					selected: parseInt(jQuery.cookie('ui-tabs-TreeActions'), 10) || null,
					cookie: { expires: 30, path: '/', name: 'ui-tabs-TreeActions' }
				});
			}
		};
	});
	
	/**
	 * @class Link for editing the profile for a logged-in member
	 * through a modal dialog.
	 * @name ss.EditMemberProfile
	 */
	$('a#EditMemberProfile').concrete('ss', function($){
		return/** @lends ss.EditMemberProfile */{
		
			onmatch: function() {
				var self = this;
			
				this.bind('click', function(e) {return self._openPopup();});
			
				$('body').append(
					'<div id="ss-ui-dialog">'
					+ '<iframe id="ss-ui-dialog-iframe" '
					+ 'marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto">'
					+ '</iframe>'
					+ '</div>'
				);
			
				var cookieVal = (jQuery.cookie) ? JSON.parse(jQuery.cookie('ss-ui-dialog')) : false;
				$("#ss-ui-dialog").dialog(jQuery.extend({
					autoOpen: false,
					bgiframe: true,
					modal: true,
					height: 300,
					width: 500,
					ghost: true,
					resizeStop: function(e, ui) {
						self._resize();
						self._saveState();
					},
					dragStop: function(e, ui) {
						self._saveState();
					},
					// TODO i18n
					title: 'Edit Profile'
				}, cookieVal)).css('overflow', 'hidden');
			
				$('#ss-ui-dialog-iframe').bind('load', function(e) {self._resize();});
			},
		
			_openPopup: function(e) {
				$('#ss-ui-dialog-iframe').attr('src', this.attr('href'));
			
				$("#ss-ui-dialog").dialog('open');
			
				return false;
			},
		
			_resize: function() {
				var iframe = $('#ss-ui-dialog-iframe');
				var container = $('#ss-ui-dialog');

				iframe.attr('width', 
					container.innerWidth() 
					- parseFloat(container.css('paddingLeft'))
					- parseFloat(container.css('paddingRight'))
				);
				iframe.attr('height', 
					container.innerHeight()
					- parseFloat(container.css('paddingTop')) 
					- parseFloat(container.css('paddingBottom'))
				);
			
				this._saveState();
			},
		
			_saveState: function() {
				var container = $('#ss-ui-dialog');
			
				// save size in cookie (optional)
				if(jQuery.cookie && container.width() && container.height()) {
					jQuery.cookie(
						'ss-ui-dialog',
						JSON.stringify({
							width: parseInt(container.width(), 10), 
							height: parseInt(container.height(), 10),
							position: [
								parseInt(container.offset().top, 10),
								parseInt(container.offset().left, 10)
							]
						}),
						{ expires: 30, path: '/'}
					);
				}
			}
		};
	});
	
	/**
	 * @class Links for viewing the currently loaded page
	 * in different modes: 'live', 'stage' or 'archived'.
	 * Automatically updates on loading a new page.
	 * @name ss.switchViewLinks
	 * @requires jquery.metadata
	 */
	$('#switchView a').concrete('ss', function($){
		
		return/** @lends ss.switchViewLinks */{
			
			/**
			 * @type DOMElement
			 */
			Form: null,
			
			onmatch: function() {
				var self = this;
				this.setForm($('#Form_EditForm'));
				
				jQuery('#Form_EditForm').bind('loadnewpage delete', function(e) {self.refresh();});
				self.refresh();
			},
			
			/**
			 * Parse new links based on the underlying form URLSegment,
			 * preserving the ?stage URL parameters if necessary.
			 */
			refresh: function() {
				// TODO Compatible with nested urls?
				var urlSegment = this.Form().find(':input[name=URLSegment]').val();
				if(urlSegment) {
					var locale = this.Form().find(':input[name=Locale]').val();
					var url = urlSegment;
					if(this.metadata().params) url += '?' + this.metadata().params;
					if(locale) url += ((url.indexOf('?') > 0) ? '&' : '?') + "locale=" + locale;
					this.attr('href', url);
				} 
				
				// hide fields if no URLSegment is present
				this.toggle((urlSegment));
			},
			
			onclick: function(e) {
				// Open in popup
				window.open($(e.target).attr('href'));
				return false;
			}
		};
	});
		
})(jQuery);














var _AJAX_LOADING = false;

Behaviour.register({

	'#MainMenu li' : {
		onclick : function(event) {
			return LeftAndMain_window_unload(); // Confirm if there are unsaved changes
			window.location.href = this.getElementsByTagName('a')[0].href;
			Event.stop(event);
		}
	}

});


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

returnFalse = function() {
	return false;
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
