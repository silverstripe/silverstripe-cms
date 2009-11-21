(function($) {

	/**
	 * @class Base edit form, provides ajaxified saving
	 * and reloading itself through the ajax return values.
	 * Takes care of resizing tabsets within the layout container.
	 * @name ss.Form_EditForm
	 */
	$('#Form_EditForm').concrete('ss',function($){
		return/** @lends ss.Form_EditForm */{	
	
		/**
		 * @type String HTML text to show when the form has been deleted.
		 */
		RemoveHtml: null,
	
		/**
		 * Suppress submission unless it is handled through ajaxSubmit()
		 */
		onsubmit: function(e) {
			return false;
		},
	
		/**
		 * @param {DOMElement} button The pressed button (optional)
		 */
		ajaxSubmit: function(button) {
			// default to first button if none given - simulates browser behaviour
			if(!button) button = this.find(':submit:first');
		
			var self = this;
		
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
			var data = this.serializeArray();
			// add button action
			data.push({name: $(button).attr('name'), value:'1'});
			$.post(
				this.attr('action'), 
				data,
				function(response) {
					$(button).removeClass('loading');
				
					self._loadResponse(response);
				}, 
				// @todo Currently all responses are assumed to be evaluated
				'script'
			);
		
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
		 * @param String url
		 * @param Function callback (Optional)
		 */
		load: function(url, callback) {
			var self = this;
			$.get(
				url, 
				function(response) {
					self._loadResponse(response);
					if(callback) callback.apply(self, [response]);
				}, 
				// @todo Currently all responses are assumed to be evaluated
				'script'
			);
		},
	
		/**
		 * Remove everying inside the <form> tag
		 * with a custom HTML fragment. Useful e.g. for deleting a page in the CMS.
		 * 
		 * @param {String} removeText
		 */
		remove: function(removeHTML) {
		
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
		 * @param {String} result Either HTML for straight insertion, or eval'ed JavaScript.
		 *  If passed as HTML, it is assumed that everying inside the <form> tag is replaced,
		 *  but the old <form> tag itself stays intact.
		 */
		_loadResponse: function(response) {
			this.cleanup();
		
			var html = response;

			// Rewrite # links
			html = html.replace(/(<a[^>]+href *= *")#/g, '$1' + window.location.href.replace(/#.*$/,'') + '#');

			// Rewrite iframe links (for IE)
			html = html.replace(/(<iframe[^>]*src=")([^"]+)("[^>]*>)/g, '$1' + $('base').attr('href') + '$2$3');

			// Prepare iframes for removal, otherwise we get loading bugs
			this.find('iframe').each(function() {
				this.contentWindow.location.href = 'about:blank';
				this.remove();
			})

			this.html(html);

			if(this.hasClass('validationerror')) {
				statusMessage(ss.i18n._t('ModelAdmin.VALIDATIONERROR', 'Validation Error'), 'bad');
			} else {
				statusMessage(ss.i18n._t('ModelAdmin.SAVED', 'Saved'), 'good');
			}

			Behaviour.apply(); // refreshes ComplexTableField
		
			// focus input on first form element
			this.find(':input:visible:first').focus();
		
			this.trigger('loadnewpage', {response: response});
		}
	}});

	/**
	 * @class All buttons in the right CMS form go through here by default.
	 * We need this onclick overloading because we can't get to the
	 * clicked button from a form.onsubmit event.
	 * @name ss.Form_EditForm.Actions.submit
	 */
	$('#Form_EditForm .Actions :submit').concrete('ss', function($){
		return/** @lends ss.Form_EditForm.Actions.submit */{
		onclick: function(e) {
			$(this[0].form).ajaxSubmit(this);
			return false;
		}
	}});
}(jQuery));