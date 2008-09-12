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
	 * Attach tabs plugin to the set of search filter and edit forms
	 */
	jQuery('ul.tabstrip').tabs();

	////////////////////////////////////////////////////////////////// 
	// Search form 
	////////////////////////////////////////////////////////////////// 

	/**
	 * Stores a jQuery reference to the last submitted search form.
	 */
	__lastSearch = null;

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
			jQuery('#form_actions_right').remove();
			Behaviour.apply();
		});
		
		return false;
	});

	/**
	 * Clear search button
	 */
	jQuery('#SearchForm_holder button[name=action_clearsearch]').click(function(e) {
		jQuery(this.form).clearForm();
		return false;
	});

	/**
	 * Column selection in search form
	  */
	jQuery('a.form_frontend_function.toggle_result_assembly').click(function(){
		var toggleElement = jQuery(this).next();
		toggleElement.toggle();
		return false;
	});
	
	jQuery('a.form_frontend_function.tick_all_result_assembly').click(function(){
		var resultAssembly = jQuery('div#ResultAssembly ul li input');
		resultAssembly.attr('checked', 'checked');
		return false;
	});
	
	jQuery('a.form_frontend_function.untick_all_result_assembly').click(function(){
		var resultAssembly = jQuery('div#ResultAssembly ul li input');
		resultAssembly.removeAttr('checked');
		return false;
	});

	////////////////////////////////////////////////////////////////// 
	// Results list 
	////////////////////////////////////////////////////////////////// 
	
	/**
	 * Table record handler for search result record
	 */
	jQuery('#right #Form_ResultsForm tbody td a')
	    .livequery('click', function(){
    		var el = jQuery(this);
    		showRecord(el.attr('href'));
    		return false;
    	})
    	.hover(
    	    function(){
        		jQuery(this).addClass('over').siblings().addClass('over')
        	}, 
        	function(){
        		jQuery(this).removeClass('over').siblings().removeClass('over')
        	}
        );

	////////////////////////////////////////////////////////////////// 
	// RHS detail form
	////////////////////////////////////////////////////////////////// 

    /**
     * RHS panel Back button
     */
	jQuery('#Form_EditForm_action_goBack').livequery('click', function() {
		if(__lastSearch) __lastSearch.trigger('submit');
		return false;
	});
	
	/**
	 * RHS panel Save button 
	 */
	jQuery('#right #form_actions_right input[name=action_doSave]').livequery('click', function(){
		var form = jQuery('#right form');
		var formAction = form.attr('action') + '?' + jQuery(this).fieldSerialize();
		
		// Post the data to save
		jQuery.post(formAction, formData(form), function(result){
			jQuery('#right #ModelAdminPanel').html(result);
			
			statusMessage("Saved");

			// TODO/SAM: It seems a bit of a hack to have to list all the little updaters here. 
			// Is livequery a solution?
			Behaviour.apply(); // refreshes ComplexTableField
			jQuery('#right ul.tabstrip').tabs();
		});

		return false;
	});
	
	/**
	 * RHS panel Delete button
	 */
	jQuery('#right #form_actions_right input[name=action_doDelete]').livequery('click', function(){
		var confirmed = confirm("Do you really want to delete?");
		if(!confirmed) return false;
		
		var form = jQuery('#right form');
		var formAction = form.attr('action') + '?' + jQuery(this).fieldSerialize();

        // The POST actually handles the delete
		jQuery.post(formAction, formData(form), function(result){
		    // On success, the panel is refreshed and a status message shown.
			jQuery('#right #ModelAdminPanel').html(result);
			
			statusMessage("Deleted");

			// TODO/SAM: It seems a bit of a hack to have to list all the little updaters here. 
			// Is livequery a solution?
			Behaviour.apply(); // refreshes ComplexTableField
			jQuery('#right ul.tabstrip').tabs();
		});
		
		return false;
	});

		
	////////////////////////////////////////////////////////////////// 
	// Import/Add form 
	////////////////////////////////////////////////////////////////// 

	/**
	 * Add object button
	 */
	jQuery('#Form_ManagedModelsSelect').submit(function(){
		className = jQuery('select option:selected', this).val();
		requestPath = jQuery(this).attr('action').replace('ManagedModelsSelect', className + '/add');
		showRecord(requestPath);
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

	////////////////////////////////////////////////////////////////// 
	// Helper functions
	////////////////////////////////////////////////////////////////// 

	/**
	 * GET a fragment of HTML to display in the right panel
 	 * @todo Should this be turned into a method on the #Form_EditForm using effen or something?
	 */
	function showRecord(uri) {
	    jQuery('#right #ModelAdminPanel').load(uri, standardStatusHandler(function(result) {
			jQuery('#SearchForm_holder').tabs();
			Behaviour.apply(); // refreshes ComplexTableField
			jQuery('#right ul.tabstrip').tabs();
		}));
	}
	
	/**
	 * Returns a flattened array of data from each field of the given form.
	 * @todo Surely jQuery has a built-in version of this?
	 */
	function formData(scope) {
		var data = {};
		jQuery('*[name]', scope).each(function(){
			var t = jQuery(this);
			if(t.attr('type') != 'checkbox' || t.attr('checked') == true) {
				data[t.attr('name')] = t.val();
			}
		});
		return data;
	}
	
	/**
	 * Standard SilverStripe status handler for ajax responses
	 * It will generate a status message out of the response, and only call the callback for successful responses
	 *
	 * To use:
	 *    Instead of passing your callback function as:
	 *       function(response) { ... }
	 * 
	 *    Pass it as this:
	 *       standardStatusHandler(function(response) { ... })
	 */
	function standardStatusHandler(callback) {
	    return function(response, status, xhr) {
	        if(status == 'success') {
	            statusMessage(xhr.statusText, "good");
	            callback(response, status, xhr);
			} else {
	            statusMessage(xhr.statusText, "bad");
			}
	    }
	}
	
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