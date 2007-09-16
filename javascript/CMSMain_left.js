if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/ajaxupdatesort';
SiteTreeHandlers.loadPage_url = 'admin/getitem';
SiteTreeHandlers.loadTree_url = 'admin/getsubtree';

_NEW_PAGES = new Array();

/**
 * Add page action
 */
addpage = Class.create();
addpage.applyTo('#addpage');
addpage.prototype = {
	initialize: function () {
		Observable.applyTo($(_HANDLER_FORMS[this.id]));
		this.getElementsByTagName('button')[0].onclick = returnFalse;
		$(_HANDLER_FORMS[this.id]).onsubmit = this.form_submit;
	},
	
	onclick : function() {
			if(treeactions.toggleSelection(this)) {
			var selectedNode = $('sitetree').firstSelected();
			if(selectedNode) {
				while(selectedNode.parentTreeNode && !selectedNode.hints.defaultChild) {
					$('sitetree').changeCurrentTo(selectedNode.parentTreeNode);
					selectedNode = selectedNode.parentTreeNode;
				}
				
				if( selectedNode.hints && selectedNode.hints.defaultChild )
					$(_HANDLER_FORMS.addpage).elements.PageType.value = selectedNode.hints.defaultChild;
			}
						
			this.o1 = $('sitetree').observeMethod('SelectionChanged', this.treeSelectionChanged.bind(this));
			this.o2 = $(_HANDLER_FORMS[this.id]).observeMethod('Close', this.popupClosed.bind(this));

			$(_HANDLER_FORMS[this.id]).elements.PageType.onchange = this.typeDropdown_change;
		}
		return false;
	},
	
	treeSelectionChanged : function(selectedNode) {
		$(_HANDLER_FORMS.addpage).elements.PageType.value = selectedNode.hints.defaultChild;
	},
	
	popupClosed : function() {
		$('sitetree').stopObserving(this.o1);
		$(_HANDLER_FORMS.addpage).stopObserving(this.o2);
	},
	
	typeDropdown_change : function() {
		// Don't do anything if we're already on an appropriate node
		var sel = $('sitetree').firstSelected();
		if(sel && sel.hints && sel.hints.allowedChildren) {
			var allowed = sel.hints.allowedChildren;
			for(i=0;i<allowed.length;i++) if(allowed[i] == this.value) return;
		}

		if( typeof siteTreeHints == 'undefined' )
			return;

		// Otherwise move to the default parent for that.
		if( siteTreeHints && siteTreeHints[this.value] ) {
			var newNode = $('sitetree').getTreeNodeByIdx(siteTreeHints[this.value].defaultParent);
			if(newNode) $('sitetree').changeCurrentTo(newNode);
		}
	},
	
	form_submit : function() {
		var st = $('sitetree');
		var parentID = st.getIdxOf(st.firstSelected());
		if(parentID && parentID.substr(0,3) == 'new') {
			alert("You have to save a page before adding children underneath it");
			
		} else if( Element.hasClassName( st.firstSelected(), "nochildren" ) ) {
			alert("You can't add children to the selected node" );
		} else {
			$(_HANDLER_FORMS.addpage).elements.ParentID.value = parentID ? parentID : 0;
		
			if( !_NEW_PAGES[parentID] )
				_NEW_PAGES[parentID] = 1;
		
			var suffix = _NEW_PAGES[parentID]++;
			Ajax.SubmitForm(_HANDLER_FORMS.addpage, "action_addpage", {
				onSuccess : Ajax.Evaluator,
				onFailure : this.showAddPageError,
				extraData: '&Suffix=' + suffix
			});
		}
		
		return false;
	},
	
	showAddPageError: function(response) {
		errorMessage('Error adding page', response);
	}
}

/**
 * Batch Actions button click action
 */
batchactions = Class.create();
batchactions.applyTo('#batchactions');
batchactions.prototype = {
	
	initialize : function() {
		Observable.applyTo($(_HANDLER_FORMS.batchactions));
	},
	onclick : function() {
		if(treeactions.toggleSelection(this)) {
			batchActionGlobals.o1 = $('sitetree').observeMethod('SelectionChanged', batchActionGlobals.treeSelectionChanged);
			batchActionGlobals.o2 = $(_HANDLER_FORMS.batchactions).observeMethod('Close', batchActionGlobals.popupClosed);
			addClass($('sitetree'),'multiselect');

			batchActionGlobals.selectedNodes = { };

			var sel = $('sitetree').firstSelected();
			if(sel && sel.className.indexOf('nodelete') == -1) {
				var selIdx = $('sitetree').getIdxOf(sel);
				batchActionGlobals.selectedNodes[selIdx] = true;
				sel.removeNodeClass('current');
				sel.addNodeClass('selected');		
			}
		}
		return false;
	}
}

// batchActionGlobals is needed because calls to observeMethod doesn't seem to preserve instance variables so a Prototype can't be used
batchActionGlobals = {
	selectedNodes: { },
	// count Int - The number of nodes selected
	count: { },
	// TODO: Remove 'new-' code http://open.silverstripe.com/ticket/875
	newNodes: { },
	treeSelectionChanged : function(selectedNode) {
		var idx = $('sitetree').getIdxOf(selectedNode);

		if(selectedNode.className.indexOf('nodelete') == -1) {
			if(selectedNode.selected) {
				selectedNode.removeNodeClass('selected');
				selectedNode.selected = false;
				batchActionGlobals.selectedNodes[idx] = false;
	
			} else {
				selectedNode.addNodeClass('selected');
				selectedNode.selected = true;
				batchActionGlobals.selectedNodes[idx] = true;
			}
		}
		
		return false;
	},
	
	popupClosed : function() {
		removeClass($('sitetree'),'multiselect');
		$('sitetree').stopObserving(batchActionGlobals.o1);
		$(_HANDLER_FORMS.batchactions).stopObserving(batchActionGlobals.o2);

		for(var idx in batchActionGlobals.selectedNodes) {
			if(batchActionGlobals.selectedNodes[idx]) {
				node = $('sitetree').getTreeNodeByIdx(idx);
				if(node) {
					node.removeNodeClass('selected');
					node.selected = false;
				}
			}
		}
		batchActionGlobals.selectedNodes = { };
	},

	getCsvIds : function() {
		var csvIDs = "";
		batchActionGlobals.count = 0;
		var st = $('sitetree');
		batchActionGlobals.newNodes = new Array();
		for(var idx in batchActionGlobals.selectedNodes) {
			if(batchActionGlobals.selectedNodes[idx]) {
				
				// Delete/Publish new nodes? (Leftover from delete code?) TODO: Remove 'new-' code http://open.silverstripe.com/ticket/875
				if( idx.match(/^new-[a-z0-9A-Z\-]+$/) ) {
					batchActionGlobals.newNodes.push( idx );
				} else {
					var i, item, childrenTopublish = st.getTreeNodeByIdx(idx).getElementsByTagName('li');
					for(i=0;item=childrenTopublish[i];i++) {
						csvIDs += (csvIDs ? "," : "") + st.getIdxOf(childrenTopublish[i]);
						batchActionGlobals.count++;
					}
					csvIDs += (csvIDs ? "," : "") + idx;
					batchActionGlobals.count++;
				}
			}
		}
		return csvIDs;
	}
}

/**
 * Publish selected pages action
 */
publishpage = Class.create();
publishpage.applyTo('#publishpage_options');
publishpage.prototype = {
	onsubmit : function() {
		csvIDs = batchActionGlobals.getCsvIds();
		if(csvIDs) {		
			this.elements.csvIDs.value = csvIDs;
			
			statusMessage('Publishing pages...');
			
			// Put an AJAXY loading icon on the button
			$('action_publish_selected').className = 'loading';
			Ajax.SubmitForm(this, null, {
				onSuccess :  function(response) {
					Ajax.Evaluator(response);
					$('action_publish_selected').className = '';
					treeactions.closeSelection($('batchactions'));
				},
				onFailure : function(response) {
					errorMessage('Error publishing pages', response);
				}
			});
		} else {
			alert("Please select at least 1 page.");
		}

		return false;
	}
}


/**
 * Delete selected pages action
 */
deletepage = Class.create();
deletepage.applyTo('#deletepage_options');
deletepage.prototype = {
	onsubmit : function() {
		csvIDs = batchActionGlobals.getCsvIds();
		if(csvIDs || batchActionGlobals.newNodes.length > 0) {
			batchActionGlobals.count += batchActionGlobals.newNodes.length;
			
			if(confirm("Do you really want to delete the " + batchActionGlobals.count + " marked pages?")) {
				this.elements.csvIDs.value = csvIDs;
				
				statusMessage('Deleting pages...');
				// TODO: Remove 'new-' code http://open.silverstripe.com/ticket/875	
				for( var idx = 0; idx < batchActionGlobals.newNodes.length; idx++ ) {
					var newNode = $('sitetree').getTreeNodeByIdx( batchActionGlobals.newNodes[idx] );
					
					if( newNode.parentTreeNode )
						newNode.parentTreeNode.removeTreeNode( newNode );
					else
						alert( newNode.id + ' has no parent node');
						
					$('Form_EditForm').reloadIfSetTo(idx);
				}
				
				batchActionGlobals.newNodes = new Array();
				// Put an AJAXY loading icon on the button
				$('action_delete_selected').className = 'loading';
				Ajax.SubmitForm(this, null, {
					onSuccess : function(response) {
						Ajax.Evaluator(response);
						$('action_delete_selected').className = '';
						treeactions.closeSelection($('batchactions'));
					},
					onFailure : function(response) {
						errorMessage('Error deleting pages', response);
					}
				});
			}
			
		} else {
			alert("Please select at least 1 page.");
		}

		return false;
	}
}

/**
 * Tree context menu
 */
TreeContextMenu = {
	'Edit this page' : function(treeNode) {
		treeNode.selectTreeNode();
	},
	'Duplicate this page' : function(treeNode) {
		// First save the page silently (without confirmation) and then duplicate the page.
		autoSave(false, treeNode.duplicatePage.bind(treeNode)); 
	},
	'Sort sub-pages' : function(treeNode) {
		var children = treeNode.treeNodeHolder().childTreeNodes();
		var sortedChildren = children.sort(function(a, b) {
			var titleA = a.aTag.innerHTML.replace(/<[^>]*>/g,'');
			var titleB = b.aTag.innerHTML.replace(/<[^>]*>/g,'');
			return titleA < titleB ? -1 : (titleA > titleB ? 1 : 0);
		});
		
		var i,child;
		for(i=0;child=sortedChildren[i];i++) {
			treeNode.appendTreeNode(child);
		}
		
		treeNode.onOrderChanged(sortedChildren);
	}
};