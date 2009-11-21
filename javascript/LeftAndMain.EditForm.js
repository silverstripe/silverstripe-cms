(function($) {

	/**
	 * @class Base edit form, provides ajaxified saving
	 * and reloading itself through the ajax return values.
	 * Takes care of resizing tabsets within the layout container.
	 * @name ss.Form_EditForm
	 * @require jquery.changetracker
	 */
	$('#Form_EditForm').concrete('ss',function($){
		return/** @lends ss.Form_EditForm */{	
	
			/**
			 * @type String HTML text to show when the form has been deleted.
			 * @todo i18n
			 */
			RemoveText: 'Removed',
			
			/**
			 * @type Object
			 */
			ChangeTrackerOptions: {},
			
			onmatch: function() {
				var self = this;
				
				this._setupChangeTracker();
				
				// Can't bind this through jQuery
				window.onbeforeunload = function(e) {return self._checkChangeTracker(false);};
				
				$._super();
			},
			
			_setupChangeTracker: function() {
				// Don't bind any events here, as we dont replace the
				// full <form> tag by any ajax updates they won't automatically reapply
				this.changetracker(this.ChangeTrackerOptions());
			},
			
			/**
			 * Checks the jquery.changetracker plugin status for this form.
			 * Usually bound to window.onbeforeunload.
			 * 
			 * @param {boolean} doConfirm
			 * @return Either a string with a confirmation message, or the result of a confirm() dialog,
			 *  based on the doConfirm parameter.
			 */
			_checkChangeTracker: function(doConfirm) {
			  var self = this;
			  
				// @todo TinyMCE coupling
				if(typeof tinyMCE != 'undefined') tinyMCE.triggerSave();
				if(self.is('.changed')) {
					var msg = ss.i18n._t('LeftAndMain.CONFIRMUNSAVED');
					// returned string will trigger a confirm() dialog, 
					// but only if the method is triggered by an event
					return (doConfirm) ? confirm(msg) : msg;
				}
			},
	
			/**
			 * Suppress submission unless it is handled through ajaxSubmit().
			 * 
			 * @param {Event} e
			 */
			onsubmit: function(e) {
				return false;
			},
	
			/**
			 * @param {DOMElement} button The pressed button (optional)
			 * @param {Function} callback Called in complete() handler of jQuery.ajax()
			 * @param {Object} ajaxOptions Object literal to merge into $.ajax() call
			 * @param {boolean} loadResponse Render response through _loadResponse() (Default: true)
			 */
			ajaxSubmit: function(button, callback, ajaxOptions, loadResponse) {
			  var self = this;
			  
				// look for save button
				if(!button) button = this.find('.Actions :submit[name=action_save]');
				// default to first button if none given - simulates browser behaviour
				if(!button) button = this.find('.Actions :submit:first');
		
				this.trigger('ajaxsubmit', {button: button});
		
				// set button to "submitting" state
				$(button).addClass('loading');
		
				// @todo TinyMCE coupling
				if(typeof tinyMCE != 'undefined') tinyMCE.triggerSave();
		
				// validate if required
				if(!this.validate()) {
					// TODO Automatically switch to the tab/position of the first error
					statusMessage("Validation failed.", "bad");

					$(button).removeClass('loading');

					return false;
				}

				// get all data from the form
				var formData = this.serializeArray();
				// add button action
				formData.push({name: $(button).attr('name'), value:'1'});
				jQuery.ajax(jQuery.extend({
					url: this.attr('action'), 
					data: formData,
					type: 'POST',
					complete: function(xmlhttp, status) {
						$(button).removeClass('loading');
						
						// TODO This should be using the plugin API
						self.removeClass('changed');
						
						if(callback) callback(xmlhttp, status);
						
						// pass along original form data to enable old/new comparisons
						if(loadResponse !== false) {
						  self._loadResponse(xmlhttp.responseText, status, xmlhttp, formData);
						}
					}, 
					dataType: 'html'
				}, ajaxOptions));
		
				return false;
			},
	
			/**
			 * Hook in (optional) validation routines.
			 * Currently clientside validation is not supported out of the box in the CMS.
			 * 
			 * @todo Placeholder implementation
			 * 
			 * @return {boolean}
			 */
			validate: function() {
				var isValid = true;
				this.trigger('validate', {isValid: isValid});
		
				return isValid;
			},
	
			/**
			 * @param {String} url
			 * @param {Function} callback (Optional)
			 * @param {ajaxOptions} Object literal merged into the jQuery.ajax() call (Optional)
			 */
			load: function(url, callback, ajaxOptions) {
			  var self = this;
			  
			  // Alert when unsaved changes are present
				if(!this._checkChangeTracker(true)) return false;
				
				return jQuery.ajax(jQuery.extend({
					url: url, 
					complete: function(xmlhttp, status) {
					  // TODO This should be using the plugin API
						self.removeClass('changed');
						
						if(callback) callback.apply(self, arguments);
					  
						self._loadResponse(xmlhttp.responseText, status, xmlhttp);
					}, 
					dataType: 'html'
				}, ajaxOptions));
			},
	
			/**
			 * Remove everying inside the <form> tag
			 * with a custom HTML fragment. Useful e.g. for deleting a page in the CMS.
			 * 
			 * @param {String} removeText Short note why the form has been removed, displayed in <p> tags.
			 *  Falls back to the default RemoveText() option (Optional)
			 */
			removeForm: function(removeText) {
				if(!removeText) removeText = this.RemoveText();
				this.html('<p>' + removeText + '</p>');
			},
	
			/**
			 * Remove all the currently active TinyMCE editors.
			 * Note: Everything that calls this externally has an inappropriate coupling to TinyMCE.
			 */
			cleanup: function() {
				if((typeof tinymce != 'undefined') && tinymce.EditorManager) {
					var id;
					for(id in tinymce.EditorManager.editors) {
						tinymce.EditorManager.editors[id].remove();
					}
					tinymce.EditorManager.editors = {};
				}
			},
	
			/**
			 * @param {String} data Either HTML for straight insertion, or eval'ed JavaScript.
			 *  If passed as HTML, it is assumed that everying inside the <form> tag is replaced,
			 *  but the old <form> tag itself stays intact.
			 * @param {String} status
			 * @param {XMLHTTPRequest} xmlhttp
			 * @param {Array} origData The original submitted data, useful to do comparisons of changed
			 *  values in new form output, e.g. to detect a URLSegment being changed on the serverside.
			 *  Array in jQuery serializeArray() notation.
			 */
			_loadResponse: function(data, status, xmlhttp, origData) {
				if(status == 'success') {
					this.cleanup();
					
					var html = data;

					// Rewrite # links
					html = html.replace(/(<a[^>]+href *= *")#/g, '$1' + window.location.href.replace(/#.*$/,'') + '#');

					// Rewrite iframe links (for IE)
					html = html.replace(/(<iframe[^>]*src=")([^"]+)("[^>]*>)/g, '$1' + $('base').attr('href') + '$2$3');

					// Prepare iframes for removal, otherwise we get loading bugs
					this.find('iframe').each(function() {
						this.contentWindow.location.href = 'about:blank';
						this.remove();
					});

					// update form content
					if(html) {
						this.html(html);
					} else {
						this.removeForm();
					}
					
					this._setupChangeTracker();
				
					// Optionally get the form attributes from embedded fields, see Form->formHtmlContent()
					for(var overrideAttr in {'action':true,'method':true,'enctype':true,'name':true}) {
						var el = this.find(':input[name='+ '_form_' + overrideAttr + ']');
						if(el) {
							this.attr(overrideAttr, el.val());
							el.remove();
						}
					}
					
					Behaviour.apply(); // refreshes ComplexTableField

					// focus input on first form element
					this.find(':input:visible:first').focus();

					this.trigger('loadnewpage', {data: data, origData: origData});
				}

				// set status message based on response
				var _statusMessage = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
				if(this.hasClass('validationerror')) {
					// TODO validation shouldnt need a special case
					statusMessage(ss.i18n._t('ModelAdmin.VALIDATIONERROR', 'Validation Error'), 'bad');
				} else {
					statusMessage(_statusMessage, (xmlhttp.status >= 400) ? 'bad' : 'good');
				}
			}
		};
	});

	/**
	 * @class All buttons in the right CMS form go through here by default.
	 * We need this onclick overloading because we can't get to the
	 * clicked button from a form.onsubmit event.
	 * @name ss.Form_EditForm.Actions.submit
	 */
	$('#Form_EditForm .Actions :submit').concrete('ss', function($){
		return/** @lends ss.Form_EditForm.Actions.submit */{
			onmatch: function() {
			  var self = this;
			  
				// TODO Fix once concrete library is updated
				this.bind('click', function(e) {return self.clickFake(e);});
			},
			clickFake: function(e) {
				$(this[0].form).concrete('ss').ajaxSubmit(this);
				return false;
			}
		};
	});
}(jQuery));