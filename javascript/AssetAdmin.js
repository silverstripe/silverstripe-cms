/**
 * Configuration for the left hand tree
 */
if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/assets/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/assets/ajaxupdatesort';
SiteTreeHandlers.loadPage_url = 'admin/assets/getitem';
SiteTreeHandlers.loadTree_url = 'admin/assets/getsubtree';
SiteTreeHandlers.showRecord_url = 'admin/assets/show/';
SiteTreeHandlers.controller_url = 'admin/assets';

var _HANDLER_FORMS = {
	addpage : 'Form_AddPageOptionsForm',
	deletepage : 'Form_DeleteItemsForm',
	sortitems : 'sortitems_options'
};

(function($) {
	/**
	 * Overload the "Create" tab to execute action instead of
	 * opening the tab content.
	 */
	$('#TreeActions-create-btn').concrete('ss', function($) {
		return {
			onmatch: function() {
				this.bind('click', function(e) {
					var form = $('form#addpage_options');
					jQuery.post(
						form.attr('action'),
						form.serialize(),
						function(data) {

						}
					);
					return false;
				})
			}
		};
	});
}(jQuery));


/**
 * Add File Action
 */
addfolder = Class.create();
addfolder.applyTo('#addpage');
addfolder.prototype = {
	initialize: function () {
		Observable.applyTo($(this.id + '_options'));
		this.getElementsByTagName('button')[0].onclick = returnFalse;
		$(this.id + '_options').onsubmit = this.form_submit;
		
	},
	
	onclick : function() {
		statusMessage('Creating new folder...');
		this.form_submit();
/*		
			if(treeactions.toggleSelection(this)) {
			var selectedNode = $('sitetree').firstSelected();
			
			if(selectedNode) {
				while(selectedNode.parentTreeNode && !selectedNode.hints.defaultChild) {
					$('sitetree').changeCurrentTo(selectedNode.parentTreeNode);
					selectedNode = selectedNode.parentTreeNode;
				}
			}
		}
*/		
		return false;
	},

	form_submit : function() {
		var st = $('sitetree');

		$('Form_AddPageOptionsForm').elements.ParentID.value = st.getIdxOf(st.firstSelected());		
		Ajax.SubmitForm('Form_AddPageOptionsForm', null, {
			onSuccess : this.onSuccess,
			onFailure : this.showAddPageError
		});
		return false;
	},
	onSuccess: function(response) {
		Ajax.Evaluator(response);
		// Make it possible to drop files into the new folder
		DropFileItem.applyTo('#sitetree li');
	},
	showAddPageError: function(response) {
		errorMessage('Error adding folder', response);
	}	
}

/**
 * Look for new files (FilesystemSync) action
 */
FilesystemSyncClass = Class.create();
FilesystemSyncClass.applyTo('#filesystemsync');
FilesystemSyncClass.prototype = {
	initialize: function () {
		this.getElementsByTagName('button')[0].onclick = returnFalse;
	},
	
	onclick : function() {
		statusMessage('Looking for new files');
        new Ajax.Request('dev/tasks/FilesystemSyncTask', {
            onSuccess: function(t) {
                statusMessage(t.responseText, "good");
            },
            onFailure: function(t) {
                errorMessage("There was an error looking for new files");
            }
		});
		return false;
	}
}

/**
 * Delete folder action
 */
deletefolder = {
	button_onclick : function() {
		if(treeactions.toggleSelection(this)) {
			deletefolder.o1 = $('sitetree').observeMethod('SelectionChanged', deletefolder.treeSelectionChanged);
			deletefolder.o2 = $('Form_DeleteItemsForm').observeMethod('Close', deletefolder.popupClosed);
			
			jQuery('#sitetree').addClass('multiselect');

			deletefolder.selectedNodes = { };

			var sel = $('sitetree').firstSelected()
			if(sel) {
				var selIdx = $('sitetree').getIdxOf(sel);
				deletefolder.selectedNodes[selIdx] = true;
				sel.removeNodeClass('current');
				sel.addNodeClass('selected');		
			}
		}
		return false;
	},

	treeSelectionChanged : function(selectedNode) {
		var idx = $('sitetree').getIdxOf(selectedNode);

		if(selectedNode.selected) {
			selectedNode.removeNodeClass('selected');
			selectedNode.selected = false;
			deletefolder.selectedNodes[idx] = false;

		} else {
			selectedNode.addNodeClass('selected');
			selectedNode.selected = true;
			deletefolder.selectedNodes[idx] = true;
		}
		
		return false;
	},
	
	popupClosed : function() {
		jQuery('#sitetree').removeClass('multiselect');
		$('sitetree').stopObserving(deletefolder.o1);
		$('Form_DeleteItemsForm').stopObserving(deletefolder.o2);

		for(var idx in deletefolder.selectedNodes) {
			if(deletefolder.selectedNodes[idx]) {
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
		for(var idx in deletefolder.selectedNodes) {
			var selectedNode = $('sitetree').getTreeNodeByIdx(idx);
			var link = selectedNode.getElementsByTagName('a')[0];
			
			if(deletefolder.selectedNodes[idx] && ( !Element.hasClassName( link, 'contents' ) || confirm( "'" + link.firstChild.nodeValue + "' contains files. Would you like to delete the files and folder?" ) ) ) 
				csvIDs += (csvIDs ? "," : "") + idx;
		}
		
		if(csvIDs) {
			$('Form_DeleteItemsForm').elements.csvIDs.value = csvIDs;
			
			statusMessage('deleting pages');

			Ajax.SubmitForm('Form_DeleteItemsForm', null, {
				onSuccess : deletefolder.submit_success,
				onFailure : function(response) {
					errorMessage('Error deleting pages', response);
				}
			});

			$('deletepage').getElementsByTagName('button')[0].onclick();
			
		} else {
			alert("Please select at least 1 page.");
		}

		return false;
	},
	
	submit_success: function(response) {
		Ajax.Evaluator(response);
		treeactions.closeSelection($('deletepage'));
	}
}

Behaviour.register({
	'#Form_EditForm_Files': {
		removeFile : function(fileID) {
			var record;
			if(record = $('record-' + fileID)) {
				record.parentNode.removeChild(record);
			} 
		}
	},	
	
	'#Form_EditForm_Files a.deletelink' : {
		onclick : function(event) {
			// Send request
			new Ajax.Request(this.href + (this.href.indexOf("?") == -1 ? "?" : "&") + "ajax=1", {
				method : 'get',
				onSuccess : Ajax.Evaluator,
				onFailure : function(response) {errorMessage('Server Error', response);}
			});
			Event.stop(event);
			return false;
		}
	},
	
	
	'#Form_EditForm' : {
		changeDetection_fieldsToIgnore : {
			'Files[]' : true
		}
	}
});

/**
 * We don't want hitting the enter key in the name field
 * to submit the form.
 */
 Behaviour.register({
 	'#Form_EditForm_Name' : {
 		onkeypress : function(event) {
 			event = (event) ? event : window.event;
 			var kc = event.keyCode ? event.keyCode : event.charCode;
 			if(kc == 13) {
 				return false;
 			}
 		}
 	}
 });

/** 
 * Initialisation function to set everything up
 */
jQuery(document).ready(function() {
	// Set up delete page
	Observable.applyTo($('Form_DeleteItemsForm'));
	if($('deletepage')) {
		$('deletepage').onclick = deletefolder.button_onclick;
		$('deletepage').getElementsByTagName('button')[0].onclick = function() { return false; };
		$('Form_DeleteItemsForm').onsubmit = deletefolder.form_submit;
		Element.hide('Form_DeleteItemsForm');
	}
	
	new CheckBoxRange($('Form_EditForm'), 'Files[]');
});