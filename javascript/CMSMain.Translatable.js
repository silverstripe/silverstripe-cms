/**
 * File: CMSMain.Translatable.js
 */
(function($) {
	$.entwine('ss', function($){
	
		/**
		 * Class: .CMSMain #Form_LangForm
		 * 
		 * Dropdown with languages above CMS tree, causing a redirect upon translation
		 */
		$('.CMSMain #Form_LangForm').entwine({
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;
			
				// monitor form loading for any locale changes
				$('#Form_EditForm').bind('loadnewpage', function(e) {
					var newLocale = $(this).find(':input[name=Locale]').val();
					if(newLocale) self.val(newLocale);
				});
			
				// whenever a new value is selected, reload the whole CMS in the new locale
				this.find(':input[name=Locale]').bind('change', function(e) {
					var url = document.location.href;
					url += (url.indexOf('?') != -1) ? '&' : '?';
					// TODO Replace existing locale GET params
					url += 'locale=' + $(e.target).val();
					document.location = url;
					return false;
				});
			
				this._super();
			}
		});
	
		/**
		 * Class: .CMSMain .createTranslation
		 * 
		 * Loads /admin/createtranslation, which will create the new record,
		 * and redirect to an edit form.
		 * 
		 * Dropdown in "Translation" tab in CMS forms, with button to 
		 * trigger translating the currently loaded record.
		 * 
		 * Requires:
		 *  jquery.metadata
		 */
		$('.CMSMain .createTranslation').entwine({
			
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;
			
				this.find(':input[name=action_createtranslation]').bind('click', function(e) {
					var form = self.parents('form');
					// redirect to new URL
					// TODO This should really be a POST request
				
					document.location.href = $('base').attr('href') + 
						jQuery(self).metadata().url + 
						'?ID=' + form.find(':input[name=ID]').val() + 
						'&newlang=' + self.find(':input[name=NewTransLang]').val() +
						'&locale=' + form.find(':input[name=Locale]').val(); 

					return false;
				});
			
				this._super();
			}
		});
	});
}(jQuery));