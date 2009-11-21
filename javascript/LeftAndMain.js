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
				var onSessionLost = function(xmlhttp, status) {
					if(xmlhttp.status > 400 || xmlhttp.responseText == 0) {
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
						complete: onSessionLost
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

function hideLoading() {
	if($('Loading')) $('Loading').style.display = 'none';
	Element.removeClassName(document.body, 'stillLoading');
}

returnFalse = function() {
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
