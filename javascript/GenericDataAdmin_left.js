/**
 * Manages searching and displaying of datarecords speccified in GenericDataAdmin.
 */
LeftPane = Class.create();
LeftPane.applyTo('#LeftPane');
LeftPane.prototype = {
	
	effectDuration: .7,
	
	initialize: function() {
		Behaviour.register('LeftPane',{
			'#Form_CreationForm_action_createRecord': {
				onclick: this.addNewRecord.bind(this)
			},

			'#Form_SearchForm_action_getResults': {
				onclick: this.filterOnChange.bind(this)
			},
			/**
			 * Small hack to keep forms for search and resultActions separate
			 * in order to avoid DOM-id-duplicates.
			 */
			'#Form_ExportForm_action_export': {
				onclick: function(e) {
					var el = Event.element(e);
					var origAction = $('Form_SearchForm').action;
					$('Form_SearchForm').action = origAction + "&" + el.name + "=1";
					$('Form_SearchForm').submit();
					$('Form_SearchForm').action = origAction;
					Event.stop(e);
					return false;
				}.bind(this)
			}
		});
		
		this.initAdvancedSearch();	
	},
	
	/**
	 * If a toggle-link is present, provide functionality to show/hide additional search-options.
	 */
	initAdvancedSearch: function() {
		var advancedSearch = $('AdvancedSearchFields');
		if(!advancedSearch) {
			return false;
		}
		
		advancedSearch.style.display = "none";
		var toggleLinks = document.getElementsBySelector('div#LeftPane .ToggleAdvancedSearchFields');
		if(toggleLinks && toggleLinks[0]) toggleLinks[0].style.display = "block";
		
		Behaviour.register('LeftPane_initAdvancedSearch',{
			"div#LeftPane .ToggleAdvancedSearchFields a":{
				onclick: function(e){
					var el = Event.element(e);
					var advancedSearchDiv = $('AdvancedSearchFields');
					if(advancedSearchDiv.style.display == 'none') {
						Effect.SlideDown(advancedSearchDiv,{duration:this.effectDuration});
						el.firstChild.nodeValue = el.firstChild.nodeValue.replace(/Show/,"Hide");
						
					} else {
						Effect.SlideUp(advancedSearchDiv,{duration:this.effectDuration});
						el.firstChild.nodeValue = el.firstChild.nodeValue.replace(/Hide/,"Show");
						
					} 
					Event.stop(e);
					return false;
				}.bind(this)
			}
		});
	},

	/**
	 * @param form DOM-Element Needed if you invoke this action externally
	 */
	addNewRecord: function(e, form) {
		var el = Event.element(e);
		var form = (form) ? form : Event.findElement(e,"form");
		var link = form.action + "&" + Form.serialize(form);

		// disable button
		Form.Element.disable(el);
		var openTab = $('Form_EditForm').getCurrentTab();
		var callback = function() {
			Form.Element.enable(this);
			statusMessage("Record created","good");
		}.bind(Event.element(e));
		$('Form_EditForm').updateCMSContent(el, openTab, link, callback);

		Event.stop(e);
		return false;
	},
	
	displayNewRecord: function(response) {
		
		$('Form_EditForm').innerHTML = response.responseText;
		onload_init_tabstrip();
		// Makes sure content-behaviour is correctly initialized in the main window.
		Behaviour.apply( $('Form_EditForm') );
	},
	
	filterOnChange: function (e, custom_onComplete) {
		try { 
			// hide existing results
			$("ResultTable_holder").innerHTML = "";
			// show loading indicator
			showIndicator('SearchLoading', $$('#Form_SearchForm .Actions')[0]);
		} catch(e) {}
		
		
		Ajax.SubmitForm(
			"Form_SearchForm", 
			"action_getResults", 
			{
				postBody : 'ajax=1',
				onSuccess : this.updateResult.bind(this),
				onFailure : function(response) {
						errorMessage('Error encountered during search', response);
				},
				onComplete : custom_onComplete
			}
		);
		
		Event.stop(e);

		return false;
	},
	
	updateResult: function(response){
		// hide loading indicator
		hideIndicator('SearchLoading');
		
		var holder;
		if(holder = $('ResultTable_holder')){
			holder.innerHTML = response.responseText;
			Behaviour.apply( $('ResultTable_holder') );
		}
	},
	
	clearFilter: function() {
		var searchForm = $('Form_SearchForm');
		var fields = searchForm.getElementsByTagName('input');
		for(var i=0; i<fields.length; i++){
			fields[0].value = '';
		}
	}
}

/**
 * Shows results provided by LeftPane.
 */
ResultTable = Class.create();
ResultTable.applyTo('#ResultTable_holder');
ResultTable.prototype = {
	initialize: function() {
		Behaviour.register('ResultTable',{
			"#LeftPane a.show":{
				onclick: function(e){
					var openTab = $('Form_EditForm').getCurrentTab();
					$('Form_EditForm').updateCMSContent(this, openTab);
					Event.stop(e);
					return false;
				}
			},
			"#ResultTable_holder a":{
				onclick: function(e){
					var el = Event.element(e);
					var link = (el.nodeName == "A") ? el : document.getElementsBySelector("a.show",el)[0];
					if(link) {
						var openTab = $('Form_EditForm').getCurrentTab();
						$('Form_EditForm').updateCMSContent(link, openTab);
					}
					Event.stop(e);
					return false;
				}
			}
		});
	}
}



function fixHeight_left() {
	fitToParent('LeftPane');
	fitToParent('Search_holder',12);
	if($('treepanes')) {
		$('treepanes').resize();
	}
}

