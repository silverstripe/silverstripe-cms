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
	deletepage : 'Form_DeleteItemsForm',
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
			alert(ss.i18n._t('TABLEFIELD.SELECTUPLOAD', 'Please select at least one file for uploading.')); 
			openTab("Root_Upload");
		} else {
			frames['AssetAdmin_upload'].document.getElementById('Form_UploadForm').submit();
		}
	}
	Event.stop(e);
	return false;
}

/**
 * Set up save folder name action
 */
Behaviour.register( {
	'#Form_EditForm_save': {
		onclick : function() {
			$('Form_EditForm').save(false, null, 'save', false);
			return false;
		}
	}
});

MarkingPropertiesButton = Class.create();
MarkingPropertiesButton.applyTo('#Form_EditForm_deletemarked', "Please select some files to delete!", 'deletemarked', 'Do you really want to delete the marked files?');

MarkingPropertiesButton.prototype = {
	initialize: function(noneCheckedError, action, confirmMessage) {
		this.noneCheckedError = noneCheckedError;
		this.action = action;
		this.confirmMessage = confirmMessage;
	},
	
	onclick: function() {
		var i, list = "", checkboxes = $('Form_EditForm').elements['Files[]'];
		if(!checkboxes) checkboxes = [];
		if(!checkboxes.length) checkboxes = [ checkboxes ];
		for(i=0;i<checkboxes.length;i++) {
			if(checkboxes[i].checked) list += (list?',':'') + checkboxes[i].value;
		}
		
		if(list == "") {
			alert(ss.i18n._t('TABLEFIELD.SELECTDELETE', 'Please select some files to delete!')); 
			return false;
			
		} else {
			$('Form_EditForm_FileIDs').value = list;
		}
		// If there is a confirmation message, show it before submitting
		if('' != this.confirmMessage) {
			// Only submit if OK button is clicked
			if (confirm(ss.i18n._t('TABLEFIELD.CONFIRMDELETEV2', 'Do you really want to delete the marked files?'))) { 
				$('Form_EditForm').save(false, null, this.action);
			}
		} else {
			$('Form_EditForm').save(false, null, this.action);
		}
		return false;
	}
};


// CheckBoxRange adapted from: http://jroller.com/page/rmcmahon?entry=checkboxrange_with_prototype
var CheckBoxRange = Class.create();

CheckBoxRange.prototype = {
	currentBox: null,
	form: null,
	field: null,

	initialize: function(form, field) {
		this.form = form;
		this.field = field;
		this.eventPossibleCheckHappened = this.possibleCheckHappened.bindAsEventListener(this);
		if(form) {
			Event.observe(form, "click", this.eventPossibleCheckHappened);
			Event.observe(form, "keyup", this.eventPossibleCheckHappened);
		}
	},
		
	possibleCheckHappened: function(event) {
		var target = Event.element(event);
			
		if ((event.button == 0 || event.keyCode == 32 || event.keyCode == 17) && 
			this.isCheckBox(target) && target.form == $(this.form) && target.name == this.field) {
			// If ctrl or shift is keys are pressed
			if ((event.shiftKey || event.ctrlKey  ) && this.currentBox)
				this.updateCheckBoxRange(this.currentBox, target);
		this.currentBox = target;
		}
	},

	isCheckBox: function(e) {
		return (e.tagName.toLowerCase() == "input" && e.type.toLowerCase() == "checkbox");
	},

	updateCheckBoxRange: function(start, end) {
		var last_clicked = end;
		var checkboxes = Form.getInputs(this.form, 'checkbox', this.field);
		var checkbox;
		var last;
		
		for (var i=0; (checkbox = checkboxes[i]); ++i) {
		if (checkbox == end) {
			last = start;
			break;
		}
		if (checkbox == start) {
			last = end;
			break;
		}
		}
		
		for (; (checkbox = checkboxes[i]); ++i) {
			if (checkbox != last_clicked && checkbox.checked != last_clicked.checked)
				checkbox.click();
			if (checkbox == last)
				break;
		}
	}
};

// SubsDraggable adapted from http://dev.rubyonrails.org/ticket/5771

// extentions for scriptaculous dragdrop.js
Object.extend(Class, {
	superrise: function(obj, names){
		names.each( function(n){ obj['super_' + n] = obj[n]; } );
		return obj;
	}
});

// Draggable that allows substitution of draggable element
var SubsDraggable = Class.create();

SubsDraggable.prototype = Object.extend({}, Draggable.prototype);
Class.superrise(SubsDraggable.prototype, ['initialize', 'startDrag', 'finishDrag', 'endDrag']);
Object.extend( SubsDraggable.prototype , {
	initialize: function(event) {
		this.super_initialize.apply(this, arguments);
		if( typeof(this.options.dragelement) == 'undefined' ) this.options.dragelement = false;
	},
	startDrag: function(event) {
		if( this.options.dragelement ) {
			this._originalElement = this.element;
			// Get the id of the file being dragged
			var beingDraggedId = this.element.id.replace('drag-Files-','');
			this.element = this.options.dragelement(this.element);
			Position.absolutize(this.element);
			Position.clone(this._originalElement, this.element);
			// Add # files being moved message
			this.element.className = 'dragfile DraggedHandle';
			// We are at least moving the 1 file being dragged
			var numMoved = 1;
			var i, checkboxes = $('Form_EditForm').elements['Files[]'];
			if(!checkboxes) checkboxes = [];
			if(!checkboxes.length) checkboxes = [ checkboxes ];
			for(i=0;i<checkboxes.length;i++) {
				// Total up the other files that are checked
				if(checkboxes[i].checked && checkboxes[i].value != beingDraggedId) {
					numMoved++;
				}
			}
			numFilesIndicator = document.createElement('span');
			numFilesIndicator.innerHTML = 'Moving ' + numMoved + ' files';
			numFilesIndicator.className = 'NumFilesIndicator';
			this.element.appendChild(numFilesIndicator);
		}
		this.super_startDrag(event);
	},
	endDrag: function(event) {
		this.super_endDrag(event);
		// Remove any remaining orphaned NumFilesIndicator spans
		// See ticket #4735
		var elts = $$('.NumFilesIndicator')
		if (elts) {
			elts.each(function(x){ Element.remove(x); });
		}
	},
	finishDrag: function(event, success) {
		this.super_finishDrag(event, success);
	
		if(this.options.dragelement){
			Element.remove(this.element);
			this.element = this._originalElement;
			this._originalElement = null;
		}
	}
});
// Creates drag element by copying the content into DIV wrapper (IE does not like TD outside of tables)
// The drag element will be deleted by dragdrop library.
function getDragElement(element){
	wrap = document.createElement('div');
	wrap.innerHTML = element.innerHTML;
	document.body.appendChild(wrap);
	return wrap;
}

// Set up DRAG handle
DragFileItem = Class.create();
DragFileItem.prototype = {
	initialize: function() {
			if (this.id)
			{
				this.draggable = new SubsDraggable(this.id, {revert:true,ghosting:false,dragelement:getDragElement});
			}
	},
	destroy: function() {
		this.draggable = null;
	}
};
DragFileItem.applyTo('#Form_EditForm_Files tr td.dragfile');

// Set up folder drop target
DropFileItem = Class.create();
DropFileItem.prototype = {
	initialize: function() {
		// Get this.recordID from the last "-" separated chunk of the id HTML attribute
		// eg: <li id="treenode-6"> would give a recordID of 6
		if(this.id && this.id.match(/-([^-]+)$/))
			this.recordID = RegExp.$1;
		this.droppable = Droppables.add(this.id, {accept:'dragfile', hoverclass:'filefolderhover',
			onDrop:function(droppedElement) {
				// Get this.recordID from the last "-" separated chunk of the id HTML attribute
				// eg: <li id="treenode-6"> would give a recordID of 6
				if(this.element.id && this.element.id.match(/-([^-]+)$/))
					this.recordID = RegExp.$1;
				$('Form_EditForm').elements['DestFolderID'].value = this.recordID;

				// Add the dropped file to the list of files to move
				var list = droppedElement.getElementsByTagName('img')[0].id.replace('drag-img-Files-','');
				var i, checkboxes = $('Form_EditForm').elements['Files[]'];
				if(!checkboxes) checkboxes = [];
				if(!checkboxes.length) checkboxes = [ checkboxes ];
				// Add each checked file to the list of ones to move
				for(i=0;i<checkboxes.length;i++) {
					if(checkboxes[i].checked) list += (list?',':'') + checkboxes[i].value;
				}
				$('Form_EditForm_FileIDs').value = list;
				$('Form_EditForm').save(false, null, 'movemarked');
			}
		});
	},
	destroy: function() {
		this.droppable = null;
		this.recordID = null;
	}
};
DropFileItem.applyTo('#sitetree li');


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
		statusMessage(ss.i18n._t('CMSMAIN.CREATINGFOLDER', 'Creating new folder...')); 
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
			onSuccess : this.onSuccess,
			onFailure : this.showAddPageError
		});
		return false;
	},
	onSuccess: function(response) {
		// Make it possible to drop files into the new folder
		DropFileItem.applyTo('#sitetree li');
	},
	showAddPageError: function(response) {
		errorMessage('Error adding folder', response);
	}	
};

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
		new Ajax.Request('admin/assets/sync', {
			onSuccess: function(t) {
				statusMessage(t.responseText, "good");
				
				// Refresh asset tree
				new Ajax.Request('admin/assets/SitetreeAsUL', {
					onSuccess: function(t) {
						Element.replace($('sitetree'), t.responseText);
						SiteTree.applyTo('#sitetree');
						
						// Reload the right panel
						var sel = $('sitetree').firstSelected();
						if(sel !== undefined) sel.selectTreeNode();
					}
				});
         },
			onFailure: function(t) {
				errorMessage("There was an error looking for new files");
			}
		});
		return false;
	}
};

/**
 * Delete folder action
 */
var deletefolder = {
	button_onclick : function() {
		if(treeactions.toggleSelection(this)) {
			deletefolder.o1 = $('sitetree').observeMethod('SelectionChanged', deletefolder.treeSelectionChanged);
			deletefolder.o2 = $('Form_DeleteItemsForm').observeMethod('Close', deletefolder.popupClosed);
			
			addClass($('sitetree'),'multiselect');

			deletefolder.selectedNodes = { };

			var sel = $('sitetree').firstSelected();
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

	form_submit : function(e) {
		var csvIDs = "";
		for(var idx in deletefolder.selectedNodes) {
			var selectedNode = $('sitetree').getTreeNodeByIdx(idx);
			var link = selectedNode.getElementsByTagName('a')[0];
			
			if(deletefolder.selectedNodes[idx] && ( !Element.hasClassName( link, 'contents' ) || confirm( "'" + link.firstChild.nodeValue + "' contains files. Would you like to delete the files and folder?" ) ) ) 
				csvIDs += (csvIDs ? "," : "") + idx;
		}
		
		if(csvIDs) {
			$('Form_DeleteItemsForm').elements.csvIDs.value = csvIDs;
			
			statusMessage(ss.i18n._t('CMSMAIN.DELETINGFOLDERS', 'Deleting folders...')); 

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
		
		Event.stop(e);
		return false;
	},
	
	submit_success: function(response) {
		treeactions.closeSelection($('deletepage'));
	}
};

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
	Observable.applyTo($('Form_DeleteItemsForm'));
	if($('deletepage')) {
		$('deletepage').onclick = deletefolder.button_onclick;
		$('deletepage').getElementsByTagName('button')[0].onclick = function() { return false; };
		// Prevent bug #4740, particularly with IE
		Behaviour.register({
			'#Form_DeleteItemsForm' : {
				onsubmit: function(event) {
					deletefolder.form_submit();
					Event.stop(event);
					return false;
				}
				}
			});
		Element.hide('Form_DeleteItemsForm');
	}
	
	new CheckBoxRange($('Form_EditForm'), 'Files[]');
});

Behaviour.register({
    '#Form_EditForm_delete_unused_thumbnails': {
        onclick : function(event) {
            Event.stop(event);
            var options = {
                method: 'get',
                onSuccess: function(t) {
                    eval(t.responseText);
                },
								parameters: {"SecurityID": ($('SecurityID')) ? $('SecurityID').value : null}
            };
            new Ajax.Request('admin/assets/deleteunusedthumbnails',options);
        }
    }
});

function refreshAsset() {
	frames[0].location.reload(0);
	frames[1].location.reload(1);
}

