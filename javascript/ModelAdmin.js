/**
 * Javascript handlers for generic model admin.
 * 
 * Most of the work being done here is intercepting clicks on form submits,
 * and managing the loading and sequencing of data between the different panels of
 * the CMS interface.
 * 
 * @todo add live query to manage application of events to DOM refreshes
 * @todo alias the $ function instead of literal jQuery
 */
(function($) {
				
	////////////////////////////////////////////////////////////////// 
	// Search form 
	////////////////////////////////////////////////////////////////// 
	
	/**
	 * If a dropdown is used to choose between the classes, it is handled by this code
	 */
	$('#ModelClassSelector select').concrete({
		onmatch: function() {
			var self = this;
			// Set up an onchange function to show the applicable form and hide all others
			this.bind('change', function(e) {
				self.find('option').each(function() {
					var $form = $('#'+$(this).val());
					if(self.val() == $(this).val()) $form.show();
					else $form.hide();
				});
			});
			
			// Initialise the form by calling this onchange event straight away
			this.change();
		}
	});
	/**
	 * Submits a search filter query and attaches event handlers
	 * to the response table, excluding the import form because 
	 * file ($_FILES) submission doesn't work using AJAX 
	 * 
	 * Note: This is used for Form_CreateForm and all Form_SearchForm_* variations
	 */
	$('#SearchForm_holder form').concrete({
		onmatch: function() {
			var self = this;
			
			this.bind('submit', function(e) {
				// Import forms are processed without ajax
				if(self.is('#Form_ImportForm')) return true;
				
				self.trigger('beforeSubmit');

				var btn = $(self[0].clickedButton);
				btn.addClass('loading');

				$('#Form_EditForm').concrete('ss').loadForm(
					self.attr('action'),
					function() {
						btn.removeClass('loading');
					},
					{data: self.serialize()}
				);

				return false;
			});
		}
	});

	/**
	 * Column selection in search form
	  */
	$('a.form_frontend_function.toggle_result_assembly').concrete({
		onclick: function(e) {
			var toggleElement = $(this).next();
			toggleElement.toggle();
			return false;
		}
	});
	
	$('a.form_frontend_function.tick_all_result_assembly').concrete({
		onclick: function(e) {
			var resultAssembly = $(this).prevAll('div#ResultAssembly').find('ul li input');
			resultAssembly.attr('checked', 'checked');
			return false;
		}
	});
	
	$('a.form_frontend_function.untick_all_result_assembly').concrete({
		onclick: function(e) {
			var resultAssembly = $(this).prevAll('div#ResultAssembly').find('ul li input');
			resultAssembly.removeAttr('checked');
			return false;
		}
	});

	/**
	 * Table record handler for search result record
	 */
	$('.resultsTable tbody td').concrete({
		onmatch: function() {
			// TODO Replace with concrete event handler
			this.bind('click', function(e) {
				var firstLink = $(this).find('a[href]');
				if(!firstLink) return;
				$('#Form_EditForm').concrete('ss').loadForm(firstLink.attr('href'));
				return false;
			});
		}
	});

	/**
	 * Add object button
	 */
	$('#Form_ManagedModelsSelect').concrete({
		onmatch: function() {
			this.bind('submit', function(){
				className = $('select option:selected', this).val();
				requestPath = $(this).attr('action').replace('ManagedModelsSelect', className + '/add');
				var $button = $(':submit', this);
				$('#Form_EditForm').concrete('ss').loadForm(
					requestPath,
					function() {
						$button.removeClass('loading');
						$button = null;
					}
				);
				return false;
			});
		}
	});
	
	/**
	 * RHS panel Delete button
	 */
	$('#Form_EditForm input[name=action_doDelete]').concrete({
		onmatch: function() {
			this.bind('click', function() {
				var confirmed = confirm(ss.i18n._t('ModelAdmin.REALLYDELETE', 'Really delete?'));
				if(!confirmed) {
					$(this).removeClass('loading');
					return false;
				}
			});
		}
	});
	
	/**
	 * Toggle import specifications
	 */
	$('.importSpec').concrete({
		onmatch: function() {
			this.hide();
			this.find('a.detailsLink').click(function() {
				$('#' + $(this).attr('href').replace(/.*#/,'')).toggle();
				return false;
			});
		}
	});
	
})(jQuery);