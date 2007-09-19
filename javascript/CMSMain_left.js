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
		this.getElementsByTagName('a')[0].onclick = returnFalse;
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
 * Delete page action
 */
deletepage = {
	button_onclick : function() {
		if(treeactions.toggleSelection(this)) {
			deletepage.o1 = $('sitetree').observeMethod('SelectionChanged', deletepage.treeSelectionChanged);
			deletepage.o2 = $(_HANDLER_FORMS.deletepage).observeMethod('Close', deletepage.popupClosed);
			addClass($('sitetree'),'multiselect');

			deletepage.selectedNodes = { };

			var sel = $('sitetree').firstSelected();
			if(sel && sel.className.indexOf('nodelete') == -1) {
				var selIdx = $('sitetree').getIdxOf(sel);
				deletepage.selectedNodes[selIdx] = true;
				sel.removeNodeClass('current');
				sel.addNodeClass('selected');		
			}
		}
		return false;
	},

	treeSelectionChanged : function(selectedNode) {
		var idx = $('sitetree').getIdxOf(selectedNode);

		if(selectedNode.className.indexOf('nodelete') == -1) {
			if(selectedNode.selected) {
				selectedNode.removeNodeClass('selected');
				selectedNode.selected = false;
				deletepage.selectedNodes[idx] = false;
	
			} else {
				selectedNode.addNodeClass('selected');
				selectedNode.selected = true;
				deletepage.selectedNodes[idx] = true;
			}
		}
		
		return false;
	},
	
	popupClosed : function() {
		removeClass($('sitetree'),'multiselect');
		$('sitetree').stopObserving(deletepage.o1);
		$(_HANDLER_FORMS.deletepage).stopObserving(deletepage.o2);

		for(var idx in deletepage.selectedNodes) {
			if(deletepage.selectedNodes[idx]) {
				node = $('sitetree').getTreeNodeByIdx(idx);
				if(node) {
					node.removeNodeClass('selected');
					node.selected = false;
				}
			}
		}
	},

	form_submit : function() {
		var csvIDs = "", count = 0;
		var st = $('sitetree');
		var newNodes = new Array();
		
		for(var idx in deletepage.selectedNodes) {
			if(deletepage.selectedNodes[idx]) {
				
				// delete new nodes
				if( idx.match(/^new-[a-z0-9A-Z\-]+$/) ) {
					newNodes.push( idx );
				} else {
					var i, item, childrenToDelete = st.getTreeNodeByIdx(idx).getElementsByTagName('li');
					for(i=0;item=childrenToDelete[i];i++) {
						csvIDs += (csvIDs ? "," : "") + st.getIdxOf(childrenToDelete[i]);
						count++;
					}
					csvIDs += (csvIDs ? "," : "") + idx;
					count++;
				}
			}
		}

		if(csvIDs || newNodes.length > 0) {
			count += newNodes.length;
			
			if(confirm("Do you really want to delete the " + count + " marked pages?")) {
				$(_HANDLER_FORMS.deletepage).elements.csvIDs.value = csvIDs;
				
				statusMessage('deleting pages');
	
				for( var idx = 0; idx < newNodes.length; idx++ ) {
					var newNode = $('sitetree').getTreeNodeByIdx( newNodes[idx] );
					
					if( newNode.parentTreeNode )
						newNode.parentTreeNode.removeTreeNode( newNode );
					else
						alert( newNode.id + ' has no parent node');
						
					$('Form_EditForm').reloadIfSetTo(idx);
				}
				
				newNodes = new Array();
	
				Ajax.SubmitForm(_HANDLER_FORMS.deletepage, null, {
					onSuccess : deletepage.submit_success,
					onFailure : function(response) {
						errorMessage('Error deleting pages', response);
					}
				});
	
				$('deletepage').getElementsByTagName('a')[0].onclick();
			}
			
		} else {
			alert("Please select at least 1 page.");
		}

		return false;
	},
	submit_success: function(response) {
		deletepage.selectedNodes = {};
		
		Ajax.Evaluator(response);
		treeactions.closeSelection($('deletepage'));
	}
}

/** 
 * Initialisation function to set everything up
 */
appendLoader(function () {
	// Set up deleet page
    if( !$('deletepage') )
        return;
    
	Observable.applyTo($(_HANDLER_FORMS.deletepage));
	$('deletepage').onclick = deletepage.button_onclick;
	$('deletepage').getElementsByTagName('a')[0].onclick = function() { return false; };
	$(_HANDLER_FORMS.deletepage).onsubmit = deletepage.form_submit;
});

/**
 * Tree context menu
 */
TreeContextMenu = {
	'Edit this page' : function(treeNode) {
		treeNode.selectTreeNode();
	},
	'Duplicate this page' : function(treeNode) {
		new Ajax.Request(baseHref() + 'admin/duplicate/' + treeNode.getIdx() + '?ajax=1', {
			method : 'get',
			onSuccess : Ajax.Evaluator,
			onFailure : function(response) {
				errorMessage('Error: ', response);
			}
		}); 
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
		
		treeNode.onOrderChanged(sortedChildren,treeNode);
	}
};