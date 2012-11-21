/**
 * LeftAndMain_left.js
 * Code for supporting the left-hand panel of all the 2-pane admin windows
 * This includes code for the action panel at the top, and the draggable, ajax-linked tree.
 */
 
if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/ajaxupdatesort';
SiteTreeHandlers.showRecord_url = 'admin/show/';
SiteTreeHandlers.controller_url = 'admin';

var _HANDLER_FORMS = {
	addpage : 'Form_AddPageOptionsForm',
	batchactions : 'batchactionsforms',
	search : 'search_options'
};

(function($) {
	$(window).bind('load', function(e) {
		// behaviour.js load handlers need to be fired before this event, so we artificially delay it
		setTimeout(function() {
			// make sure current ID of loaded form is actually selected in tree
			var tree = $('#sitetree')[0], id = $('#Form_EditForm :input[name=ID]').val();
			if(!id) id = 0;
			if(tree) tree.setCurrentByIdx(id);
		}, 200);
	});
}(jQuery));

/**
 * Overload this with a real context menu if necessary
 */
var TreeContextMenu = null;

/**
 * Extra methods for the tree when used in the LHS of the CMS
 */
TreeAPI = Class.create();
TreeAPI.prototype = {

	setCustomURL: function(url, arguments) {
		this.customURL = url;
		this.customArguments = $H(arguments);
	},
	
	clearCustomURL: function() {
		this.customURL = this.customArguments = null;
	},
	
	url: function(args) {
		var args = $H(args).merge(this.customArguments);

		var url = this.customURL ? this.customURL : SiteTreeHandlers.loadTree_url; 
		url = url + (url.match(/\?/) ? '&' : '?') + args.toQueryString();

		return url;
	},
	
	reload: function(options) {
		this.innerHTML =  ss.i18n._t('LOADING', 'loading...'); 

		var args = {ajax:1, ID:0};
		if ($('LangSelector')) args.locale = $('LangSelector').value;
		
		var url = this.url(args);
		
		var self = this;
		new Ajax.Request(url, {
			onSuccess: function(response){
				self.innerHTML = response.responseText;
				self.castAsTreeNode(self.firstChild);
				if (options && options.onSuccess) options.onSuccess(response);
			},
			onFailure: function(response){
				errorMessage('error loading tree', response);
				if (options.onError) options.onError(response);
			}
		});
	},
	
	/**
	 * Perform the given code on the each tree node with the given index.
	 * There could be more than one :-)
	 * @param idx The index of the tree node
	 * @param code A method to be executed, that will be passed the tree node
	 */
	performOnTreeNode: function(idx, code) {
		var treeNode = this.getTreeNodeByIdx(idx);
		if(!treeNode) return;
		
		if(treeNode.className.indexOf('manyparents') == -1) {
			code(treeNode);
		
		} else {
			var i,item,allNodes = this.getElementsByTagName('li');
			for(i=0;item=allNodes[i];i++) {
				if(treeNode.id == item.id) code(item);
			}
		}
	},
	
	getTreeNodeByIdx : function(idx) {
		if(!idx) idx = "0";
		var node = document.getElementById('record-' + idx);
		if(idx == "0" && !node) node = document.getElementById('record-root');
		return node;
	},
	getIdxOf : function(treeNode) {
		if(treeNode && treeNode.id && treeNode.id.match(/record-([0-9a-zA-Z\-]+)$/))
			return RegExp.$1;
	},
	createTreeNode : function(idx, title, pageType) {
		var i;
		var node = document.createElement('li');
		node.id = 'record-' + idx;
		node.className = pageType;

		var aTag = document.createElement('a');
		aTag.href = SiteTreeHandlers.showRecord_url + idx;
		aTag.title = 'Page type: ' + pageType;
		aTag.innerHTML = title;
		node.appendChild(aTag);

		SiteTreeNode.create(node, this.options);
		
		return node;
	},
	
	setNodeIdx : function(idx, newIdx) {
		this.performOnTreeNode(idx, function(treeNode) {
			treeNode.id = 'record-' + newIdx;
			var aTag = treeNode.getElementsByTagName('a')[0];
			aTag.href = aTag.href.replace(idx, newIdx);
		});
	
		var treeNode = this.getTreeNodeByIdx(idx);
	},
	
	setNodeTitle : function(idx, title) {
		this.performOnTreeNode(idx, function(treeNode) {
			var aTag = treeNode.getElementsByTagName('a')[0];
			aTag.innerHTML = title;
		});
	},

	addNodeClassByIdx : function(idx, className) {
		this.performOnTreeNode(idx, function(treeNode) {
			treeNode.addNodeClass(className);
		});
	},
	
	setNodeIcon: function(idx, oldClassName, newClassName) {
		this.performOnTreeNode(idx, function(treeNode) {
			treeNode.className = treeNode.className.replace(oldClassName,newClassName);
			treeNode.aSpan.className = 'a ' + treeNode.className.replace('closed','spanClosed');
			var aTag = treeNode.getElementsByTagName('a')[0];
			aTag.title = 'Page type: ' + newClassName;
			treeNode.setIconByClass();
		});
	},
	
	/**
	 * Set the parent ID of a tree node
	 */
	setNodeParentID: function (idx, parentID) {
		var treeNode = this.getTreeNodeByIdx(idx);
		var parentNode = this.getTreeNodeByIdx(parentID);
		parentNode.appendTreeNode(treeNode);
	},
	
	setCurrentByIdx : function(idx) {
		if(this.selected) {
			var i,item;
			for(i=0;item=this.selected[i];i++) {
				item.removeNodeClass('current');
			}
		}

		__tree = this;
		__tree.selected = [];

		this.performOnTreeNode(idx, function(treeNode) {
			__tree.selected.push(treeNode);
			treeNode.expose();
			treeNode.addNodeClass('current');
		});
	},
	
	changeCurrentTo : function(newNode) {
		if(this.selected) {
			var i,item;
			for(i=0;item=this.selected[i];i++) {
				item.removeNodeClass('current');
			}
		}
		
		newNode.addNodeClass('current');

		this.selected = [newNode];
		newNode.expose();
	},
	
	firstSelected : function() {
		if(this.selected) return this.selected[0];
	}
};

/**
 * Extra methods for the tree node when used in the LHS of the CMS
 */
TreeNodeAPI = Class.create();
TreeNodeAPI.prototype = {
	selectTreeNode : function() {
		if(this.getElementsByTagName('a')[0].href) {
			_AJAX_LOADING = true;
			if($('sitetree').notify('SelectionChanged', this)) {
				autoSave(true, this.getPageFromServer.bind(this));
			}
		}
	},
		
	getPageFromServer : function() {
		$('Form_EditForm').getPageFromServer(this.id.replace('record-',''), this);
		_AJAX_LOADING = false;
	},
	ajaxExpansion : function() {
		this.addNodeClass('loading');
		var ul = this.treeNodeHolder(false);
		ul.innerHTML = ss.i18n._t('LOADING', 'loading...'); 
		
		// Any attempts to add children to this page should, in fact, cue them up for insertion later
		ul.cuedNewNodes = [];
		ul.appendTreeNode = function(node) {
			this.cuedNewNodes[this.cuedNewNodes.length] = node;
		}
		
		var args = {ajax:1, ID:this.getIdx()};
		
		// Add current locale for any subtree selection
		if ($('LangSelector')) args.locale = $('LangSelector').value;
		
		// If the tree is selectable, we have to show all available children without
		// artificial limitations from the serverside (minNodeCount). This is a measure
		// to ensure no unexpanded nodes are missed in batch selection
		if(Element.hasClassName('sitetree', 'multiselect')) args.minNodeCount = 0;
		
		url = this.tree.url(args); 
		
		new Ajax.Request(url, {
			onSuccess : this.installSubtreeAndRefresh.bind(this),
			onFailure : this.showSubtreeLoadingError
		});
	},
	
	installSubtreeAndRefresh : function(response){
		this.installSubtree(response);
		if(this.tree.className.indexOf('multiselect') != -1) {
			batchActionGlobals.refreshSelected(this);
		}
	},
	
	showSubtreeLoadingError: function(response) { 
		errorMessage('error loading subtree', response);
	},
	
	/**
	 * Context menu
	 */
	oncontextmenu: function(event) {
		if(TreeContextMenu) {
			if(!event) event = window.event;
			createContextMenu(event, this, TreeContextMenu);
			Event.stop(event);
			return false;
		}
	},
	duplicatePage: function() {  
		// Pass the parent ID to the duplicator, which helps ensure that multi-parent pages are duplicated into the node that the user clicked
		var parentClause = "";
		if(this.parentTreeNode && this.parentTreeNode.getIdx) {
			parentClause = "&parentID=" + this.parentTreeNode.getIdx();
		}
		var token = $$('input[name=SecurityID]')[0].value;
		var url = baseHref() + 'admin/duplicate/' + this.getIdx() + '?ajax=1' + parentClause + '&SecurityID=' + token;

		new Ajax.Request(url, {
			method : 'get',
			onFailure : function(response) {
				errorMessage('Error: ', response);
			}
		}); 
	},
	duplicatePageWithChildren: function() {  
		var token = $$('input[name=SecurityID]')[0].value;
		var url = baseHref() + 'admin/duplicatewithchildren/' + this.getIdx() + '?ajax=1&SecurityID=' + token;
		new Ajax.Request(url, {
			method : 'get',
			onFailure : function(response) {
				errorMessage('Error: ', response);
			}
		}); 
	}
}




/**
 * In the case of Tree & DraggableTree, the root tree and the sub-trees all use the same class.
 * In this case, however, SiteTree has a much bigger API and so SiteSubTree is smaller.
 */
SiteSubTree = Class.extend('Tree').extend('TreeAPI');
SiteSubTree.prototype = {
	castAsTreeNode: function(li) {
		behaveAs(li, SiteTreeNode, this.options);
	}
}

 
/**
 * Our SiteTree class extends the tree object with a richer manipulation API.
 * The server will send a piece javascript that uses these functions.  In this way, the server
 * has flexibility over its operation, but the Script->HTML interface is kept client-side.
 */
SiteTree = Class.extend('SiteSubTree');
SiteTree.prototype = {
	initialize : function() {
		this.Tree.initialize();
		
		/*
		if(!this.tree.selected) this.tree.selected = [];
		var els = this.getElementsByTagName('li');
		for(var i=0;i<els.length;i++) if(els[i].className.indexOf('current') > -1) {
			this.tree.selected.push(els[i]);
			break;
		}
		*/
		
		this.observeMethod('SelectionChanged', this.interruptLoading.bind(this) );
	},
	destroy: function () {
		if(this.Tree) this.Tree.destroy();
		this.Tree = null;
		this.SiteSubTree = null;
		this.TreeAPI = null;
		this.selected = null;
	},
	
	/**
	 * Stop the currently loading node from loading.
	 */
	interruptLoading: function( newLoadingNode ) {
		if( this.loadingNode ) this.loadingNode.removeNodeClass('loading');
		this.loadingNode = newLoadingNode;
	}
}

SiteTreeNode = Class.extend('TreeNode').extend('TreeNodeAPI');
SiteTreeNode.prototype = {
	initialize: function(options) {
		this.TreeNode.initialize(options);

		if(this.className && this.className.match(/ *([^ ]+)( +|$)/)) {
			if((typeof siteTreeHints != 'undefined') && siteTreeHints[ RegExp.$1 ]) {
				this.hints = siteTreeHints[ RegExp.$1 ];
				this.dropperOptions = { 
					accept : (this.hints.allowedChildren && (this.className.indexOf('nochildren') == -1))
						 ? this.hints.allowedChildren : 'none' 
				};
			}
		}
		
		if(this.className.indexOf('current') > -1) {
			if(!this.tree.selected) this.tree.selected = [];
			this.tree.selected.push(this);
		}

		if(!this.hints) this.hints = {}
	},
	
	destroy: function () {
		if(this.TreeNode) this.TreeNode.destroy();
		this.TreeNode = null;
		this.TreeNodeAPI = null;
	},
	
	castAsTree: function(childUL) {
		behaveAs(childUL, SiteSubTree, this.options);
		if(this.draggableObj) childUL.makeDraggable();
	},
		
	onselect: function() {
		this.selectTreeNode();
		return false;
	},

	

	/**
	 * Drag'n'drop handlers - Ajax saving
	 */
	onParentChanged : function(node, oldParent, newParent) {
		if(newParent.id.match(/^record-new/)) {
			alert("You must save the page before dragging children into it");
			return false;
		}
		
		if( node == newParent || node.getIdx() == newParent.getIdx() ) {
			alert("You cannot add a page to itself");
			return false;
		}
		
		if(node.innerHTML.toLowerCase().indexOf('<del') > -1) {
			alert("You can't moved deleted pages");
			return false;
		}
		
		if( Element.hasClassName( newParent, 'nochildren' ) ) {
			alert("You can't add children to that node");
			return false;
		}
		
		var currentlyOpenPageID = 0;
		if($('Form_EditForm').elements.ID) currentlyOpenPageID = $('Form_EditForm').elements.ID.value;

		statusMessage(ss.i18n._t('CMSMAIN.SAVING'), '', true);
		var token = $$('input[name=SecurityID]')[0].value;
		new Ajax.Request(SiteTreeHandlers.parentChanged_url, {
			method : 'post', 
			postBody : 'ID=' + node.getIdx() + '&ParentID=' + newParent.getIdx() + '&CurrentlyOpenPageID=' + currentlyOpenPageID + '&SecurityID=' + token,
			onFailure : function(response) {
				errorMessage('error saving parent', response);
			}
		});
		
		return true;
	},

	/**
	 * Called when the tree has been resorted
	 * nodeList is a list of all the nodes in the correct rder
	 * movedNode is the node that actually got moved to trigger this resorting
	 */
	onOrderChanged : function(nodeList, movedNode) {
		statusMessage(ss.i18n._t('CMSMAIN.SAVING'), '', true);

		var i, parts = Array();
		sort = 0;
		
		for(i=0;i<nodeList.length;i++) {
			if(nodeList[i].getIdx && nodeList[i].getIdx()) {
				parts[parts.length] = 'ID[]=' + nodeList[i].getIdx();
			
				// Ensure that the order of new records is preserved when they are moved THEN saved
				if( nodeList[i].id.indexOf("record-new") == 0 )
					if( $('Form_EditForm_ID') && ( 'record-' + $('Form_EditForm_ID').value == nodeList[i].id ) )
						if( $('Form_EditForm_Sort') )
							$('Form_EditForm_Sort').value = ++sort;
			}
		}
		
		if(movedNode.getIdx && movedNode.getIdx()) {
			parts[parts.length] = 'MovedNodeID=' + movedNode.getIdx();
		}

		var currentlyOpenPageID = 0;
		if($('Form_EditForm').elements.ID) currentlyOpenPageID = $('Form_EditForm').elements.ID.value;

		if(parts) {
			var token = $$('input[name=SecurityID]')[0].value;
			new Ajax.Request(SiteTreeHandlers.orderChanged_url, {
				method : 'post', 
				postBody : parts.join('&') + '&CurrentlyOpenPageID=' + currentlyOpenPageID + '&SecurityID=' + token,
				onSuccess : function(response) {
					movedNode.removeNodeClass('loading');
					if( $('sitetree').selected && $('sitetree').selected[0]){
						var idx =  $('sitetree').selected[0].getIdx();
						if(idx){
							$('Form_EditForm').getPageFromServer(idx);
						}
					}
				},
				onFailure : function(response) {
					errorMessage('error saving order', response);
				}
			});
		}
		
		return true;
	}	
}

/**
 * Action panel at the top of the left hand tree
 */
treeactions = {
	toggleSelection : function(li) {
		li = (li.tagName.toLowerCase() == 'button') ? li.parentNode : li;

		// Deselect
		if(li.selected) this.closeSelection(li, true);
		else this.openSelection(li, true);

		fixHeight_left();
		return li.selected;
	},
	closeSelection : function(li, nested) {
		li = (li.tagName.toLowerCase() == 'button') ? li.parentNode : li;

		Element.hide(_HANDLER_FORMS[li.id]);
		removeClass(li, 'selected');
		li.selected = false;
		$(_HANDLER_FORMS[li.id]).notify('Close');
		if(li.onclose) li.onclose();

		if(typeof nested == 'undefined') fixHeight_left();
	},
	openSelection: function(li, nested) {
		li = (li.tagName.toLowerCase() == 'button') ? li.parentNode : li;
		
		addClass(li, 'selected');
		Element.show(_HANDLER_FORMS[li.id]);
		li.selected = true;
	
		// Deselect siblings
		var i, set = li.parentNode.getElementsByTagName('li');
		for(i=0;i<set.length;i++) if(li != set[i] && set[i].selected) {
			this.closeSelection(set[i], true);
		}
		
		if(typeof nested == 'undefined') fixHeight_left();
	}
}

function fixHeight_left() {
	if($('treepanes')) {
		fitToParent('treepanes');
		var st = $('treepanes');
		if(st.resize) st.resize();
	}
	else fitToParent('sitetree_holder');
}

// Build the site tree
SiteTree.applyTo('#sitetree');

/**
 * Reorganise action checkbox 
 */
ReorganiseAction = Class.create();
ReorganiseAction.applyTo('#sortitems');
ReorganiseAction.prototype = {
	initialize: function () {
	},
	
	onclick : function() {
		if ($('sitetree').isDraggable == false) {
			$('sitetree').makeDraggable();
		} else {
			$('sitetree').stopBeingDraggable();
		}
	}
}
