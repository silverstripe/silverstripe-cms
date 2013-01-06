if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/ajaxupdatesort';
SiteTreeHandlers.loadPage_url = 'admin/getitem';
SiteTreeHandlers.loadTree_url = 'admin/getsubtree';

_NEW_PAGES = new Array();

/**
 * Add page action
 */
var addpageclass;
addpageclass = Class.create();
addpageclass.applyTo('#addpage');
addpageclass.prototype = {
	originalValues: new Array(),
	initialize: function () {
		Observable.applyTo($(_HANDLER_FORMS[this.id]));
		this.getElementsByTagName('button')[0].onclick = returnFalse;
		$(_HANDLER_FORMS[this.id]).onsubmit = this.form_submit;
		
		// Save the original page types in to this object
		if ($(_HANDLER_FORMS.addpage).elements.PageType) {
			var options = $(_HANDLER_FORMS.addpage).elements.PageType.options;
			for(var i = 0; i < options.length; i++) {
				this.originalValues.push({
					'value': options[i].value,
					'label': options[i].innerHTML
				});
			}
		
			var selectedNode = $('sitetree').firstSelected();
			if(selectedNode) this.showApplicableChildrenPageTypes(selectedNode.hints);
		}
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
	
	// Reset the page types dropdown to its original state
	resetPageTypeOptions: function() {
		var select = $(_HANDLER_FORMS.addpage).elements.PageType;
		while (select.childNodes.length >= 1) {	select.removeChild(select.firstChild); } 
		for(var i = 0; i < this.originalValues.length; i++) {
			var option = document.createElement('option');
			option.value = this.originalValues[i].value;
			option.innerHTML = this.originalValues[i].label;
			select.appendChild(option);
		}
	},
	
	// Hide the <option> elements in the new page <select> unless
	// they are in the allowChildren array of the selected tree node
	showApplicableChildrenPageTypes: function(hints) {
		this.resetPageTypeOptions();
		if (typeof hints.allowedChildren != 'undefined') {
			var select = $(_HANDLER_FORMS.addpage).elements.PageType;
			
			var toRemove = new Array();
			for(var i = 0; i < select.options.length; i++) {
				var itemFound = false;
				for(var j = 0; j < hints.allowedChildren.length; j++) {
					if (select.options[i].value == hints.allowedChildren[j]) { itemFound = true; break; }
				}
				if (!itemFound) toRemove.push(select.options[i]);
			}
			for(var i = 0; i < toRemove.length; i++) { select.removeChild(toRemove[i]); }
		}
	},
	
	treeSelectionChanged : function(selectedNode) {
		this.showApplicableChildrenPageTypes(selectedNode.hints);
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
				onFailure : function(response) { if (response.status == 403) {
					alert('You cannot add that page at that location.');
				}},
				extraData: '&Suffix=' + suffix
			});
		}
		
		return false;
	}
};

/**
 * Search button click action
 */
var searchclass;
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
};

var SiteTreeFilter = Class.create();
SiteTreeFilter.applyTo('#siteTreeFilterList');
SiteTreeFilter.prototype = {
	initialize: function () {
	},
	reapplyIfNeeded: function() {
		if(this.options[this.selectedIndex].value != 'all') this.onchange();
	},
	onchange : function() {
		var value = this.options[this.selectedIndex].value;
		
		if(value != 'all') { 
			$('sitetree').setCustomURL(SiteTreeHandlers.controller_url+'/getfilteredsubtree?filter='+escape(value));
		} else {
			$('sitetree').clearCustomURL();
		}

		// We can't update the tree while it's draggable; it gets b0rked.
		var __makeDraggableAfterUpdate = false;
		if($('sitetree').isDraggable) {
			$('sitetree').stopBeingDraggable();
			__makeDraggableAfterUpdate = true;
		}
	
		var indicator = $('siteTreeFilterActionIndicator');
		indicator.style.display = 'inline';
	
		$('sitetree').reload({
			onSuccess: function() {
				indicator.style.display = 'none';
				if(__makeDraggableAfterUpdate) $('sitetree').makeDraggable();
				batchActionGlobals.refreshSelected();
			},
			onFailure: function(response) {
				errorMessage('Could not update tree', response);
			}
		});
	}
};
/**
 * Control the site tree filter
 */
var SiteTreeFilterForm = Class.create();
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
			});
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
			$('sitetree').setCustomURL(SiteTreeHandlers.controller_url + '/getfilteredsubtree?filter=CMSSiteTreeFilter_Search', filters);
			
			// Disable checkbox tree controls that currently don't work with search.
			// @todo: Make them work together
			if ($('sitetree').isDraggable) $('sitetree').stopBeingDraggable();
			document.getElementsBySelector('.checkboxAboveTree input[type=checkbox]').each(function(el){
				el.value = false; el.disabled = true;	
			});
		}
		else {
			// Reset URL to default
			$('sitetree').clearCustomURL();
			
			// Enable checkbox tree controls
			document.getElementsBySelector('.checkboxAboveTree input[type=checkbox]').each(function(el){
				el.disabled = false;	
			});
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
};

/**
 * Add Criteria Drop-down onchange action which allows more criteria to be shown
 */
var SiteTreeFilterAddCriteria = Class.create();
SiteTreeFilterAddCriteria.applyTo('#SiteTreeFilterAddCriteria');
SiteTreeFilterAddCriteria.prototype = {
	onchange : function() {
		Element.show('Container' + this.value);
		// Element.show('Text' + this.value);
		// Element.show('Input' + this.value);
		this.selectedIndex = 0; //reset selected criteria to prompt 
	}
};

/**
 * Batch Actions button click action
 */
var batchactionsclass = Class.create();
batchactionsclass.applyTo('#batchactions');
batchactionsclass.prototype = {
	
	initialize : function() {
		Observable.applyTo($(_HANDLER_FORMS.batchactions));
	},
	onclick : function() {
		if(treeactions.toggleSelection(this)) {
			this.multiselectTransform();
			this.actionChanged();
		}
		return false;
	},
	
	actionChanged: function() {
		// Show parameters form, if necessary
		var urlSegment = $('choose_batch_action').value.split('/').pop();
		if ($('BatchActionParameters_'+urlSegment)) {
			jQuery('#BatchActionParameters .params').hide();
			jQuery('#BatchActionParameters_'+urlSegment).show();
			jQuery('#BatchActionParameters').show();
		} else {
			jQuery('#BatchActionParameters').hide();
		}
		
		// Don't show actions that have failed from the previous execution
		batchActionGlobals.removeFailures();
		
		batchActionGlobals.refreshSelected();
	},
	
	multiselectTransform : function() {
		batchActionGlobals.o1 = $('sitetree').observeMethod('SelectionChanged', batchActionGlobals.treeSelectionChanged);
		batchActionGlobals.o2 = $(_HANDLER_FORMS.batchactions).observeMethod('Close', batchActionGlobals.popupClosed);
	
		addClass($('sitetree'),'multiselect');
	
		batchActionGlobals.selectedNodes = { };

		var selectedNode = $('sitetree').firstSelected();
		if(selectedNode && selectedNode.className.indexOf('nodelete') == -1) {
			var selIdx = $('sitetree').getIdxOf(selectedNode);
			batchActionGlobals.selectedNodes[selIdx] = true;
			selectedNode.removeNodeClass('current');
			selectedNode.addNodeClass('selected');	
			selectedNode.open();	
			
			// Open all existing children, which might trigger further
			// ajaxExansion calls to ensure all nodes are selectable
			var children = selectedNode.getElementsByTagName('li');
			for(var i=0; i<children.length; i++) {
				children[i].open();
			}
		}
	}
};

// batchActionGlobals is needed because calls to observeMethod doesn't seem to preserve instance variables so a Prototype can't be used
batchActionGlobals = {
	selectedNodes: { },
	// count Int - The number of nodes selected
	count: function() {
		return batchActionGlobals.getIds().length;
	},
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
				// Open node in order to allow proper selection of children
				if(Element.hasClassName(selectedNode, 'unexpanded')) {
					selectedNode.open();
				}
				
				// Select node
				selectedNode.addNodeClass('selected');
				selectedNode.selected = true;
				batchActionGlobals.selectedNodes[idx] = true;
				
				// Open all existing children, which might trigger further
				// ajaxExansion calls to ensure all nodes are selectable
				var children = selectedNode.getElementsByTagName('li');
				for(var i=0; i<children.length; i++) {
					children[i].open();
				}
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
	
	getIds: function() {
		var csvIDs = new Array();
		var st = $('sitetree');
		batchActionGlobals.newNodes = new Array();
		for(var idx in batchActionGlobals.selectedNodes) {
			if(batchActionGlobals.selectedNodes[idx]) {
				
				// Delete/Publish new nodes? (Leftover from delete code?) TODO: Remove 'new-' code http://open.silverstripe.com/ticket/875
				if( idx.match(/^new-[a-z0-9A-Z\-]+$/) ) {
					batchActionGlobals.newNodes.push( idx );
				} else {
					if(csvIDs.indexOf(idx) == -1) {
						csvIDs.push(idx);
					}
				}
			}
		}
		return csvIDs;
	},
	getCsvIds : function() {
		return (batchActionGlobals.getIds().toString());
	},
	refreshSelected : function(rootNode) {
		var st = $('sitetree');
		
		for(var idx in batchActionGlobals.selectedNodes) {
			st.getTreeNodeByIdx(idx).addNodeClass('selected');
			st.getTreeNodeByIdx(idx).selected = true;
		}

		// Default to refreshing the entire tree
		if(rootNode == null) rootNode = st;

		/// If batch actions is enabled, then enable/disable the appropriate tree fields
		if($('batchactionsforms').style.display != 'none' && $('choose_batch_action').value) {
			// Collect list of visible tree IDs 
			var ids = [];
			jQuery(rootNode).find('li').each(function() {
				var id = parseInt(this.id.replace('record-',''));
				if(id) ids.push(id);
				
				// Disable the nodes while the ajax request is being processed
				this.removeNodeClass('nodelete');
				this.addNodeClass('treeloading');
			});
		
			// Post to the server to ask which pages can have this batch action applied
			var applicablePagesURL = $('choose_batch_action').value + '/applicablepages?csvIDs=' + ids.join(',');
			jQuery.getJSON(applicablePagesURL, function(applicableIDs) {
				var i;
				var applicableIDMap = {};
				for(i=0;i<applicableIDs.length;i++) applicableIDMap[applicableIDs[i]] = true;
			
				// Set a CSS class on each tree node indicating which can be batch-actioned and which can't
				jQuery(rootNode).find('li').each(function() {
					this.removeNodeClass('treeloading');

					var id = parseInt(this.id.replace('record-',''));
					if(id) {
						if(applicableIDMap[id] === true) {
							this.removeNodeClass('nodelete');
						} else {
							// De-select the node if it's non-applicable
							delete batchActionGlobals.selectedNodes[id];

							this.removeNodeClass('selected');
							this.addNodeClass('nodelete');
						}
					}
				});
			});
		}
	},

	/**
	 * Deselect all nodes in the tree
	 */
	deselectAll : function() {
		batchActionGlobals.selectedNodes = {}
		jQuery('#sitetree').find('li').each(function() {
			this.removeNodeClass('selected');
			this.selected = false;
		});
	},

	/**
	 * Remove the indications of failed batch actions
	 */
	removeFailures : function() {
		jQuery('#sitetree').find('li.failed').each(function() {
			this.removeNodeClass('failed');
		});
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
};


Behaviour.register({
	'#choose_batch_action' : {
		onchange : function() {
			$('batchactions').actionChanged();
		}
	}
});

/**
 * Publish selected pages action
 */
var publishpage = Class.create();
publishpage.applyTo('#batchactions_options');
publishpage.prototype = {
	onsubmit : function() {
		csvIDs = batchActionGlobals.getCsvIds();
		if(csvIDs) {		
			var optionEl = $('choose_batch_action').options[$('choose_batch_action').selectedIndex];
			var actionText = optionEl.text;
			
			var confirmationURL = $('choose_batch_action').value + '/confirmation?csvIDs=' + csvIDs;
			jQuery.getJSON(confirmationURL, function(data) {
				// If a custom alert has been provided, show that.
				// otherwise, show the default one
				if (data.alert) {
					if (!confirm(data.content)) return false;
				} else {
					if(!confirm(ss.i18n.sprintf(ss.i18n._t('CMSMAIN.SELECTMOREPAGES'), batchActionGlobals.count()))) { 
						return false;
					}
				}
				
				$('batchactions_options').submitform();
			});
		} else {
			alert(ss.i18n._t('CMSMAIN.SELECTONEPAGE'));
		}

		return false;
	},
	
	submitform: function() {
		csvIDs = batchActionGlobals.getCsvIds();

		var optionEl = $('choose_batch_action').options[$('choose_batch_action').selectedIndex];
		var optionParams = eval(optionEl.className);
		var ingText = optionParams.doingText;
			
		this.elements.csvIDs.value = csvIDs;

		// Select form submission URL
		this.action = $('choose_batch_action').value;

		// Loading indicator
		statusMessage(ingText);
		$('batchactions_go').className = 'loading';

		// Don't show actions that have failed from the previous execution
		batchActionGlobals.removeFailures();

		// Submit form
		Ajax.SubmitForm(this, null, {
			onSuccess :  function(response) {
				$('batchactions_go').className = '';
				batchActionGlobals.deselectAll();
			},
			onFailure : function(response) {
				errorMessage('Error ' + ingText, response);
			}
		});
	}
};


/**
 * Delete selected pages action
 */
var deletepage;
deletepage = Class.create();
deletepage.applyTo('#Form_DeleteItemsForm');
deletepage.prototype = {
	onsubmit : function() {
		csvIDs = batchActionGlobals.getCsvIds();
		if(csvIDs || batchActionGlobals.newNodes.length > 0) {
			
			if(confirm(ss.i18n.sprintf(
				ss.i18n._t('CMSMAIN.REALLYDELETEPAGES'),
				batchActionGlobals.count()
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
				$('Form_DeleteItemsForm_action_deleteitems').className = 'loading';
				Ajax.SubmitForm(this, null, {
					onSuccess : function(response) {
						$('Form_DeleteItemsForm_action_deleteitems').className = '';
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
};

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
