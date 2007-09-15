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
	addpage : 'addpage_options',
	deletepage : 'deletepage_options',
	sortitems : 'sortitems_options'
};

/**
 * Top-right actions
 */

function action_upload_right(e) {
	if(frames['AssetAdmin_upload'].document && frames['AssetAdmin_upload'].document.getElementById('Form_UploadForm')) {
		// make sure at least one file is selected for upload
		var values = "";
		var inputs = $A(frames['AssetAdmin_upload'].document.getElementsByTagName("input"));
		inputs.each(function(input) {
			if(input.type == "file") values += input.value;
		}.bind(this));
		
		if(values.length == 0) {
			alert("Please select at least one file for uploading");
			openTab("Root_Upload");
		} else {
			frames['AssetAdmin_upload'].document.getElementById('Form_UploadForm').submit();
		}
	}
	Event.stop(e);
	return false;
}

function action_deletemarked_right() {
	$('action_deletemarked_options').onComplete = function() {}

	if(confirm("Do you really want to delete the marked files?")) {
		$('action_deletemarked_options').send();
	}
}
function action_movemarked_right() {
	$('action_movemarked_options').toggle();
}

MarkingPropertiesForm = Class.extend('ActionPropertiesForm');
MarkingPropertiesForm.applyTo('#action_movemarked_options', "Please select some files to move!");
MarkingPropertiesForm.applyTo('#action_deletemarked_options', "Please select some files to delete!");

MarkingPropertiesForm.prototype = {
	initialize: function(noneCheckedError) {
		this.noneCheckedError = noneCheckedError;
	},
	
	send: function() {
		var i, list = "", checkboxes = $('Form_EditForm').elements['Files[]'];
		if(!checkboxes) checkboxes = [];
		if(!checkboxes.length) checkboxes = [ checkboxes ];
		for(i=0;i<checkboxes.length;i++) {
			if(checkboxes[i].checked) list += (list?',':'') + checkboxes[i].value;
		}
		
		if(this.elements.DestFolderID && !this.elements.DestFolderID.value) {
			alert("Please select a folder to move files to!");
			return false;

		} else if(list == "") {
			alert(this.noneCheckedError);
			return false;
			
		} else {
			this.elements.FileIDs.value = list;
			return this.ActionPropertiesForm.send();
		}
	}
}


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

		$('addpage_options').elements.ParentID.value = st.getIdxOf(st.firstSelected());		
		Ajax.SubmitForm('addpage_options', null, {
			onSuccess : Ajax.Evaluator,
			onFailure : this.showAddPageError
		});
		
		return false;
	},
	
	showAddPageError: function(response) {
		errorMessage('Error adding folder', response);
	}	
}


/**
 * Delete folder action
 */
deletefolder = {
	button_onclick : function() {
		if(treeactions.toggleSelection(this)) {
			deletefolder.o1 = $('sitetree').observeMethod('SelectionChanged', deletefolder.treeSelectionChanged);
			deletefolder.o2 = $('deletepage_options').observeMethod('Close', deletefolder.popupClosed);
			
			addClass($('sitetree'),'multiselect');

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
		removeClass($('sitetree'),'multiselect');
		$('sitetree').stopObserving(deletefolder.o1);
		$('deletepage_options').stopObserving(deletefolder.o2);

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
			$('deletepage_options').elements.csvIDs.value = csvIDs;
			
			statusMessage('deleting pages');

			Ajax.SubmitForm('deletepage_options', null, {
				onSuccess : deletefolder.submit_success,
				onFailure : function(response) {
					errorMessage('Error deleting pages', response);
				}
			});

			$('deletepage').getElementsByTagName('a')[0].onclick();
			
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
			ajaxLink(this.href);
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
appendLoader(function () {
	// Set up delete page
	Observable.applyTo($('deletepage_options'));
	if($('deletepage')) {
		$('deletepage').onclick = deletefolder.button_onclick;
		$('deletepage').getElementsByTagName('a')[0].onclick = function() { return false; };
		$('deletepage_options').onsubmit = deletefolder.form_submit;
	}
});