/**
 * File: LeftAndMain.Tree.js
 */

(function($) {
	
	$.entwine('ss', function($){
	
		$('#sitetree_ul').entwine({
			onmatch: function() {
				this._super();
				
				/**
				 * @todo Selectable tree with multiselect (toggled via "Batch Actions" panel)
				 * @todo Fix initial, unnecessary html_data replacement of existing tree (see below)
				 * @todo Icon and page type hover support
				 * @todo Sorting of sub nodes (originally placed in context menu)
				 * @todo Refresh after language <select> change (with Translatable enabled)
				 * @todo Automatic load of full subtree via ajax on node checkbox selection (minNodeCount = 0)
				 *  to avoid doing partial selection with "hidden nodes" (unloaded markup)
				 * @todo Add siteTreeHints to field (as "data-hints" attribute with serialized JSON instead of javascript global variable)
				 * @todo Disallow drag'n'drop when not matching "allowedChildren" or "allowedParents" (see siteTreeHints)
				 * @todo Disallow drag'n'drop when node has "noChildren" set (see siteTreeHints)
				 * @todo Disallow moving of pages marked as deleted 
				 * @todo Enforce sitetreeHints rules on page creation ("allowedChildren", "noChildren") -
				 *  most likely by server response codes rather than clientside
				 * @todo "defaultChild" when creating a page (sitetreeHints)
				 * @todo Duplicate page (originally located in context menu)
				 * @todo Update tree node title information and modified state after reordering (response is a JSON array)
				 * 
				 * Tasks most likely not required after moving to a standalone tree:
				 * 
				 * @todo Context menu - to be replaced by a bezel UI
				 * @todo Refresh form for selected tree node if affected by reordering (new parent relationship)
				 * @todo Cancel current form load via ajax when new load is requested (synchronous loading)
				 * @todo When new edit form is loaded, automatically: Select matching node, set correct parent,
				 *  update icon and title
				 */
					this
						.jstree({
							'core': {
								'initially_open': ['record-0'],
								'animation': 0
							},
							'html_data': {
								// TODO Hack to avoid ajax load on init, see http://code.google.com/p/jstree/issues/detail?id=911
								'data': this.html(),
								'ajax': {
									'url': this.data('url-tree'),
									'data': function(node) {
										return { ID : $(node).data("id") ? $(node).data("id") : 0 , ajax: 1};
									}
								}
							},
							'ui': {
								"select_limit" : 1,
								'initially_select': [this.find('.current').attr('id')]
							},
							 "crrm": {
								'move': {
									// Check if a node is allowed to be moved.
									// Caution: Runs on every drag over a new node
									'check_move': function(data) {
										var movedNode = $(data.o), newParent = $(data.np), 
											isMovedOntoContainer = data.ot.get_container()[0] == data.np[0];
										var isAllowed = (
											// Don't allow moving the root node
											movedNode.data('id') != 0 
											// Only allow moving node inside the root container, not before/after it
											&& (!isMovedOntoContainer || data.p == 'inside')
										);
										return isAllowed;
									}
								}
							},
							'dnd': {
								"drop_target" : false,
								"drag_target" : false
							},
							'themes': {
								'theme': 'apple'
							},
							// 'plugins': ['html_data', 'ui', 'dnd', 'crrm', 'themeroller']
							'plugins': ['html_data', 'ui', 'dnd', 'crrm', 'themes']
						})
						.bind('before.jstree', function(e, data) {
							if(data.func == 'start_drag') {
								// Only allow drag'n'drop if it has been specifically enabled
								if(!$('input[id=sortitems]').is(':checked')) {
									e.stopImmediatePropagation();
									return false;
								}
							}
						})
						// TODO Move to EditForm logic
						.bind('select_node.jstree', function(e, data) {
							var node = data.rslt.obj, loadedNodeID = $('#Form_EditForm :input[name=ID]').val()

							// Don't allow reloading of currently selected node,
							// mainly to avoid doing an ajax request on initial page load
							if($(node).data('id') == loadedNodeID) return;

							var url = $(node).find('a:first').attr('href');
							if(url && url != '#') {
								var xmlhttp = $('#Form_EditForm').loadForm(
									url,
									function(response) {}
								);
							} else {
								$('#Form_EditForm').removeForm();
							}
						})
						.bind('move_node.jstree', function(e, data) {
							var movedNode = data.rslt.o, newParentNode = data.rslt.np, oldParentNode = data.inst._get_parent(movedNode);
							var siblingIDs = $.map($(movedNode).siblings().andSelf(), function(el) {
								return $(el).data('id');
							});

							$.ajax({
								'url': this.data('url-savetreenode'),
								'data': {
									ID: $(movedNode).data('id'), 
									ParentID: $(newParentNode).data('id') || 0,
									SiblingIDs: siblingIDs
								}
							});
						});
			}
			
			
		});
	});

}(jQuery));