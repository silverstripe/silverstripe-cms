/**
 * Configuration for the left hand tree
 */
if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/security/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/security/ajaxupdatesort';
SiteTreeHandlers.loadPage_url = 'admin/security/getitem';
SiteTreeHandlers.loadTree_url = 'admin/security/getsubtree';
SiteTreeHandlers.showRecord_url = 'admin/security/show/';
SiteTreeHandlers.controller_url = 'admin/security';

_HANDLER_FORMS['deletegroup'] = 'deletegroup_options';

/**
 * Add page action
 * @todo Remove duplication between this and the CMSMain Add page action
 */
var addgroup = {
	button_onclick : function() {
		addgroup.form_submit();
		return false;
	},
	
	form_submit : function() {
		var st = $('sitetree');
		$('addgroup_options').elements.ParentID.value = st.firstSelected() ? st.getIdxOf(st.firstSelected()) : 0;
		Ajax.SubmitForm('addgroup_options', null, {
			onFailure : function(response) {
				errorMessage('Error adding page', response);
			}
		});
		
		return false;
	}
}

/**
 * Delete page action
 */
var deletegroup = {
	button_onclick : function() {
		/*if( $('deletegroup_options').style.display == 'none' )
			$('deletegroup_options').style.display = 'block';
		else
			$('deletegroup_options').style.display = 'none';*/
		
		if(treeactions.toggleSelection(this)) {
			$('deletegroup_options').style.display = 'block';
			
			deletegroup.o1 = $('sitetree').observeMethod('SelectionChanged', deletegroup.treeSelectionChanged);
			deletegroup.o2 = $('deletegroup_options').observeMethod('Close', deletegroup.popupClosed);
			addClass($('sitetree'),'multiselect');

			deletegroup.selectedNodes = { };
			
			var sel = $('sitetree').firstSelected();
			if(sel && sel.className.indexOf('nodelete') == -1) {
				var selIdx = $('sitetree').getIdxOf(sel);
				deletegroup.selectedNodes[selIdx] = true;
				sel.removeNodeClass('current');
				sel.addNodeClass('selected');		
			}
		} else 
			$('deletegroup_options').style.display = 'none';
			
		return false;
	},

	treeSelectionChanged : function(selectedNode) {
		var idx = $('sitetree').getIdxOf(selectedNode);

		if(selectedNode.className.indexOf('nodelete') == -1) {
			if(selectedNode.selected) {
				selectedNode.removeNodeClass('selected');
				selectedNode.selected = false;
				deletegroup.selectedNodes[idx] = false;
	
			} else {
				selectedNode.addNodeClass('selected');
				selectedNode.selected = true;
				deletegroup.selectedNodes[idx] = true;
			}
		}
		
		return false;
	},
	
	popupClosed : function() {
		removeClass($('sitetree'),'multiselect');
		$('sitetree').stopObserving(deletegroup.o1);
		$('deletegroup_options').stopObserving(deletegroup.o2);

		for(var idx in deletegroup.selectedNodes) {
			if(deletegroup.selectedNodes[idx]) {
				node = $('sitetree').getTreeNodeByIdx(idx);
				if(node) {
					node.removeNodeClass('selected');
					node.selected = false;
				}
			}
		}
	},

	form_submit : function() {
		var csvIDs = "";
		for(var idx in deletegroup.selectedNodes) {
			if(deletegroup.selectedNodes[idx]) csvIDs += (csvIDs ? "," : "") + idx;
		}
		if(csvIDs) {
			if(confirm("Do you really want to delete these groups?")) {
				$('deletegroup_options').elements.csvIDs.value = csvIDs;
	
				Ajax.SubmitForm('deletegroup_options', null, {
					onSuccess : function(response) {
						var sel;
						if((sel = $('sitetree').firstSelected()) && sel.parentNode) sel.addNodeClass('current');
						else $('Form_EditForm').innerHTML = "";
	
						treeactions.closeSelection($('deletegroup'));
					},
					onFailure : function(response) {
						errorMessage('Error deleting pages', response);
					}
				});
	
				$('deletegroup').getElementsByTagName('button')[0].onclick();
			}
		} else {
			alert("Please select at least one group.");
		}

		return false;
	}
}


/** 
 * Initialisation function to set everything up
 */
Behaviour.addLoader(function () {
	// Set up add page
	Observable.applyTo($('addgroup_options'));
	if($('addgroup')) {
		$('addgroup').onclick = addgroup.button_onclick;
		$('addgroup').getElementsByTagName('button')[0].onclick = function() {return false;};
		$('addgroup_options').onsubmit = addgroup.form_submit;
	}

	// Set up delete page
	Observable.applyTo($('deletegroup_options'));
	if($('deletegroup')) {
		$('deletegroup').onclick = deletegroup.button_onclick;
		$('deletegroup').getElementsByTagName('button')[0].onclick = function() {return false;};
		$('deletegroup_options').onsubmit = deletegroup.form_submit;
	}
});