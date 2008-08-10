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
jQuery(document).ready(function() {
	/**
	 * Stores a jQuery reference to the last submitted search form.
	 */
	__lastSearch = null;

	/**
	 * GET a fragment of HTML to display in the right panel
	 */
	function showRecord(uri) {
		jQuery.get(uri, function(result){
			jQuery('#right #ModelAdminPanel').html(result);
			jQuery('#SearchForm_holder').tabs();
			
			// TODO/SAM: It seems a bit of a hack to have to list all the little updaters here. 
			// Is livequery a solution?
			Behaviour.apply(); // refreshes ComplexTableField
			jQuery('#right ul.tabstrip').tabs();
		});
	}

	jQuery('#Form_EditForm_action_goBack').livequery('click', function() {
		if(__lastSearch) __lastSearch.trigger('submit');
		return false;
	});
	
	/**
	 * POST a hash of form submitted data to the given endpoint
	 */
	function saveRecord(uri, data) {
		jQuery.post(uri, data, function(result){
			jQuery('#right #ModelAdminPanel').html(result);

			// TODO/SAM: It seems a bit of a hack to have to list all the little updaters here. 
			// Is livequery a solution?
			Behaviour.apply(); // refreshes ComplexTableField
			jQuery('#right ul.tabstrip').tabs();
		});
	}
	
	/**
	 * Returns a flattened array of data from each field
	 * of the given form 
	 */
	function formData(scope) {
		var data = {};
		jQuery('*[name]', scope).each(function(){
			var t = jQuery(this);
			var val = (t.attr('type') == 'checkbox') ? (t.attr('checked') == true) ? 1 : 0 : t.val();
			data[t.attr('name')] = val;
		});
		return data;
	}
	
	/**
	 * Find the selected data object and load its create form
	 */
	jQuery('#Form_ManagedModelsSelect').submit(function(){
		className = jQuery('select option:selected', this).val();
		requestPath = jQuery(this).attr('action').replace('ManagedModelsSelect', className + '/add');
		showRecord(requestPath);
		return false;
	});
	
	/**
	 * attach generic action handler to all forms displayed in the #right panel
	 */
	jQuery('#right #form_actions_right .action').livequery('click', function(){
		form = jQuery('#right form');
		var formAction = form.attr('action') + '?' + jQuery(this).fieldSerialize();
		saveRecord(formAction, formData(form));
		return false;
	});
	
	/**
	 * Attach tabs plugin to the set of search filter and edit forms
	 */
	jQuery('ul.tabstrip').tabs();
	
	/**
	 * Submits a search filter query and attaches event handlers
	 * to the response table
	 * 
	 * @todo use livequery to manage ResultTable click handlers
	 */
	jQuery('#SearchForm_holder .tab form').submit(function(){
		__lastSearch = jQuery(this);
		
		form = jQuery(this);
		data = formData(form);
		jQuery.get(form.attr('action'), data, function(result){
			jQuery('#right #ModelAdminPanel').html(result);
			
			Behaviour.apply();
			
			jQuery('#right #ModelAdminPanel tbody td a').click(function(){
				var el = jQuery(this);
				showRecord(el.attr('href'));
				//el.parent().parent().find('td').removeClass('active');
				//el.addClass('active').siblings().addClass('active');
				return false;
			}).hover(function(){
						jQuery(this).addClass('over').siblings().addClass('over')
					}, function(){
						jQuery(this).removeClass('over').siblings().removeClass('over')
					});
		});
		
		return false;
	});
	
	/**
	 * Toggle import specifications
	 */
	jQuery('#Form_ImportForm_holder .spec .details').hide();
	jQuery('#Form_ImportForm_holder .spec a.detailsLink').click(function() {
		jQuery('#' + jQuery(this).attr('href').replace(/.*#/,'')).toggle();
		return false;
	});
	
});

/**
 * @todo Terrible HACK, but thats the cms UI...
 */
function fixHeight_left() {
	fitToParent('LeftPane');
	fitToParent('Search_holder',12);
	fitToParent('ResultTable_holder',12);
}

function prepareAjaxActions(actions, formName, tabName) {
	// @todo HACK Overwrites LeftAndMain.js version of this method to avoid double form actions
	// (by new jQuery and legacy prototype) 
	return false;
}