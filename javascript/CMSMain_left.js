if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/ajaxupdatesort';
SiteTreeHandlers.loadPage_url = 'admin/getitem';
SiteTreeHandlers.loadTree_url = 'admin/getsubtree';

_NEW_PAGES = new Array();

/**
 * Add page action
 */
addpageclass = Class.create();
addpageclass.applyTo('#addpage');
addpageclass.prototype = {
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
		// TODO: Remove 'new-' code http://open.silverstripe.com/ticket/875
		if(parentID && parentID.substr(0,3) == 'new') {
			alert(ss.i18n._t('CMSMAIN.WARNINGSAVEPAGESBEFOREADDING'));
			
		} else if( Element.hasClassName( st.firstSelected(), "nochildren" ) ) {
			alert(ss.i18n._t('CMSMAIN.CANTADDCHILDREN') );
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
		errorMessage(ss.i18n._t('CMSMAIN.ERRORADDINGPAGE'), response);
	}
}

/**
 * Search button click action
 */
searchclass = Class.create();
searchclass.applyTo('#search');
searchclass.prototype = {
	initialize : function() {
		Observable.applyTo($(_HANDLER_FORMS.search));
	},
	onclick : function() {
		if(treeactions.toggleSelection(this)) {
			this.o2 = $(_HANDLER_FORMS[this.id]).observeMethod('Close', this.popupClosed.bind(this));
		}
		return false;
	},
	popupClosed : function() {
		$(_HANDLER_FORMS.search).stopObserving(this.o2);
		batchActionGlobals.unfilterSiteTree();
	}
}

/**
 * Show deleted pages checkbox
 */
ShowDeletedPagesAction = Class.create();
ShowDeletedPagesAction.applyTo('#showdeletedpages');
ShowDeletedPagesAction.prototype = {
	initialize: function () {
	},
	
	onclick : function() {
		if(this.checked) { 
			$('sitetree').setCustomURL(SiteTreeHandlers.controller_url+'/getshowdeletedsubtree');
		} else {
			$('sitetree').clearCustomURL();
		}

		// We can't update the tree while it's draggable; it gets b0rked.
		var __makeDraggableAfterUpdate = false;
		if($('sitetree').isDraggable) {
			$('sitetree').stopBeingDraggable();
			__makeDraggableAfterUpdate = true;
		}
		
		$('sitetree').reload({
			onSuccess: function() {
				if(__makeDraggableAfterUpdate) $('sitetree').makeDraggable();
			},
			onFailure: function(response) {
				errorMessage('Could not update tree', response);
			}
		});

	}
}

/**
 * Show only drafts checkbox click action
 */
showonlydrafts = Class.create();
showonlydrafts.applyTo('#publishpage_show_drafts');
showonlydrafts.prototype = {
	onclick : function() {
		if(this.checked) { 
			$('sitetree').setCustomURL(SiteTreeHandlers.controller_url+'/getfilteredsubtree', {Status:'Saved'});
		} else {
			$('sitetree').clearCustomURL();
		}
		
		$('sitetree').reload({
			onSuccess: function() {
				statusMessage(ss.i18n._t('CMSMAIN.FILTEREDTREE'),'good');
			},
			onFailure: function(response) {
				errorMessage(ss.i18n.sprintf(
					ss.i18n._t('CMSMAIN.ERRORFILTERPAGES'),
					response.responseText
				));
			}
		});
	}
}

/**
 * Control the site tree filter
 */
SiteTreeFilterForm = Class.create();
SiteTreeFilterForm.applyTo('form#search_options');
SiteTreeFilterForm.prototype = {
	initialize: function() {
		var self = this;
		Form.getElements(this).each(function(el){
			if (el.type == 'submit') el.onclick = function(){self.clicked = $F(this);};
		});
	},
	onsubmit: function() {
		var filters = $H();
		
		if (this.clicked == 'Clear') {
			Form.getElements(this).each(function(el){
				if (el.type == 'text') el.value = '';
				else if (el.type == 'select-one') el.value = 'All';
			});
			document.getElementsBySelector('.SearchCriteriaContainer', this).each(function(el){
				Element.hide(el);
			})
		}
		else {
			Form.getElements(this).each(function(el){
				if (el.type == 'text') {
					if ($F(el)) filters[el.name] = $F(el);
				}
				else if (el.type == 'select-one') {
					if ($F(el) && $F(el) != 'All') filters[el.name] = $F(el);
				}
			});
		}
		
		if (filters.keys().length) {
			// Set new URL
			$('sitetree').setCustomURL(SiteTreeHandlers.controller_url + '/getfilteredsubtree', filters);
			
			// Disable checkbox tree controls that currently don't work with search.
			// @todo: Make them work together
			if ($('sitetree').isDraggable) $('sitetree').stopBeingDraggable();
			document.getElementsBySelector('.checkboxAboveTree input[type=checkbox]').each(function(el){
				el.value = false; el.disabled = true;	
			})
		}
		else {
			// Reset URL to default
			$('sitetree').clearCustomURL();
			
			// Enable checkbox tree controls
			document.getElementsBySelector('.checkboxAboveTree input[type=checkbox]').each(function(el){
				el.disabled = false;	
			})
		}
		
		$('SiteTreeSearchButton').className = $('SiteTreeSearchClearButton').className = 'hidden';
		$('searchIndicator').className = 'loading';
		
		$('sitetree').reload({
			onSuccess :  function(response) {
				$('SiteTreeSearchButton').className = $('SiteTreeSearchClearButton').className = 'action';
				$('searchIndicator').className = '';
				statusMessage('Filtered tree','good');
			},
			onFailure : function(response) {
				errorMessage('Could not filter site tree<br />' + response.responseText);
			}
		});
		
		return false;
	}
}

/**
 * Add Criteria Drop-down onchange action which allows more criteria to be shown
 */
SiteTreeFilterAddCriteria = Class.create();
SiteTreeFilterAddCriteria.applyTo('#SiteTreeFilterAddCriteria');
SiteTreeFilterAddCriteria.prototype = {
	onchange : function() {
		Element.show('Container' + this.value);
		// Element.show('Text' + this.value);
		// Element.show('Input' + this.value);
		this.selectedIndex = 0; //reset selected criteria to prompt 
	}
}

/**
 * Batch Actions button click action
 */
batchactionsclass = Class.create();
batchactionsclass.applyTo('#batchactions');
batchactionsclass.prototype = {
	
	initialize : function() {
		Observable.applyTo($(_HANDLER_FORMS.batchactions));
	},
	onclick : function() {
		if(treeactions.toggleSelection(this)) {
			this.multiselectTransform();
		}
		return false;
	},
	
	multiselectTransform : function() {
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
		var csvIDs = new Array();
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
						if(csvIDs.indexOf(st.getIdxOf(childrenTopublish[i])) == -1) {
							csvIDs.push(st.getIdxOf(childrenTopublish[i]));
						}
					}
					if(csvIDs.indexOf(idx) == -1) {
						csvIDs.push(idx);
					}
				}
			}
		}
		batchActionGlobals.count=csvIDs.length;	
		return (csvIDs.toString());
	},
	unfilterSiteTree : function() {
		// Reload the site tree if it has been filtered
		if ($('SiteTreeIsFiltered').value == 1) {
			// Show all items in Site Tree again
			new Ajax.Request( 'admin/SiteTreeAsUL' + '&ajax=1', {
				onSuccess: function( response ) {
					$('sitetree_ul').innerHTML = response.responseText;
					Behaviour.apply($('sitetree_ul'));
					$('SiteTreeIsFiltered').value = 0;
					$('batchactions').multiselectTransform();
					statusMessage(ss.i18n._t('CMSMAIN.SUCCESSUNFILTER'),'good');
				},
				onFailure : function(response) {
					errorMessage(ss.i18n.sprintf(
						ss.i18n._t('CMSMAIN.ERRORUNFILTER'),
						response.responseText
					));
				}
			});
		}
	}
}

/**
 * Publish selected pages action
 */
publishpage = Class.create();
publishpage.applyTo('#batchactions_options');
publishpage.prototype = {
	onsubmit : function() {
		csvIDs = batchActionGlobals.getCsvIds();
		if(csvIDs) {		
			var optionEl = $('choose_batch_action').options[$('choose_batch_action').selectedIndex];
			var actionText = optionEl.text;
			var optionParams = eval(optionEl.className);
			var ingText = optionParams.doingText;

			// Confirmation
			if(!confirm("You have " + batchActionGlobals.count + " pages selected.\n\nDo your really want to " + actionText.toLowerCase() + "?")) {
				return false;
			}

			this.elements.csvIDs.value = csvIDs;

			// Select form submission URL
			this.action = $('choose_batch_action').value;
			
			// Loading indicator
			statusMessage(ingText);
			$('batchactions_go').className = 'loading';
			
			// Submit form
			Ajax.SubmitForm(this, null, {
				onSuccess :  function(response) {
					Ajax.Evaluator(response);
					$('batchactions_go').className = '';
					treeactions.closeSelection($('batchactions'));
				},
				onFailure : function(response) {
					errorMessage('Error ' + ingText, response);
				}
			});
		} else {
			alert(ss.i18n._t('CMSMAIN.SELECTONEPAGE'));
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
			
			if(confirm(ss.i18n.sprintf(
				ss.i18n._t('CMSMAIN.REALLYDELETEPAGES'),
				batchActionGlobals.count
			))) {
				this.elements.csvIDs.value = csvIDs;
				
				statusMessage(ss.i18n._t('CMSMAIN.DELETINGPAGES'));
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
						errorMessage(ss.i18n._t('CMSMAIN.ERRORDELETINGPAGES'), response);
					}
				});
			}
			
		} else {
			alert(ss.i18n._t('CMSMAIN.SELECTONEPAGE'));
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
	'Duplicate page and children' : function(treeNode) {
		// First save the page silently (without confirmation) and then duplicate the page.
		autoSave(false, treeNode.duplicatePageWithChildren.bind(treeNode)); 
	},
	'Duplicate just this page' : function(treeNode) {
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
		
		treeNode.onOrderChanged(sortedChildren,treeNode);
	}
};