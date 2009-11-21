if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/ajaxupdatesort';
SiteTreeHandlers.loadPage_url = 'admin/getitem';
SiteTreeHandlers.loadTree_url = 'admin/getsubtree';

/**
 * Tree context menu
 */
TreeContextMenu = {
	'Edit this page' : function(treeNode) {
		treeNode.selectTreeNode();
	},
	'Duplicate page and children' : function(treeNode) {
		// First save the page silently (without confirmation) and then duplicate the page.
		jQuery('#Form_EditForm').concrete('ss').ajaxSubmit(null, treeNode.duplicatePageWithChildren.bind(treeNode)); 
	},
	'Duplicate just this page' : function(treeNode) {
		// First save the page silently (without confirmation) and then duplicate the page.
		jQuery('#Form_EditForm').concrete('ss').ajaxSubmit(null, treeNode.duplicatePageWithChildren.bind(treeNode)); 
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
