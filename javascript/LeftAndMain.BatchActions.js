/**
 * File: LeftAndMain.BatchActions.js
 */
(function($) {
	$.entwine('ss', function($){
	
		/**
		 * Class: #Form_BatchActionsForm
		 * 
		 * Batch actions which take a bunch of selected pages,
		 * usually from the CMS tree implementation, and perform serverside
		 * callbacks on the whole set. We make the tree selectable when the jQuery.UI tab
		 * enclosing this form is opened.
		 * 
		 * Events:
		 *  register - Called before an action is added.
		 *  unregister - Called before an action is removed.
		 */
		$('#Form_BatchActionsForm').entwine({
	
			/**
			 * Variable: Tree 
			 * (DOMElement)
			 */
			Tree: null,
		
			/**
			 * Variable: Actions
			 * (Array) Stores all actions that can be performed on the collected IDs as
			 * function closures. This might trigger filtering of the selected IDs,
			 * a confirmation message, etc.
			 */
			Actions: [],
		
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;
				
				this.setTree($('#sitetree')[0]);
			
				$(this.getTree()).bind('select_node.jstree', function(e, data) {
					self._treeSelectionChanged(data.rslt.obj);
				});
			
				// if tab which contains this form is shown, make the tree selectable
				$('#TreeActions').bind('tabsselect', function(e, ui) {
					// if we are selecting another tab, or the panel is visible (meaning about to be closed),
					// disable tree selection and reset any values. Otherwise enable it.
					if($(ui.panel).attr('id') != 'TreeActions-batchactions' || $(ui.panel).is(':visible')) {
						// @TODO: this is unneccessarily fired also when switching between two other tabs
						$(self.getTree()).removeClass('multiselect');
					} else {
						self._multiselectTransform();
						self.serializeFromTree();
					}
				});
				
				this._super();
			},
		
			/**
			 * Function: register
			 * 
			 * Parameters:
			 * 
			 * 	(String) type - ...
			 * 	(Function) callback - ...
			 */
			register: function(type, callback) {
				this.trigger('register', {type: type, callback: callback});
				var actions = this.getActions();
				actions[type] = callback;
				this.setActions(actions);
			},
		
			/**
			 * Function: unregister
			 * 
			 * Remove an existing action.
			 * 
			 * Parameters:
			 * 
			 *  {String} type
			 */
			unregister: function(type) {
				this.trigger('unregister', {type: type});
			
				var actions = this.getActions();
				if(actions[type]) delete actions[type];
				this.setActions(actions);
			},
		
			/**
			 * Function: _isActive
			 * 
			 * Determines if we should allow and track tree selections.
			 * 
			 * Todo:
			 *  Too much coupling with tabset
			 * 
			 * Returns:
			 *  (boolean)
			 */
			_isActive: function() {
				return $('#TreeActions-batchactions').is(':visible');
			},
		
			/**
			 * Function: refreshSelected
			 * 
			 * Ajax callbacks determine which pages is selectable in a certain batch action.
			 * 
			 * Parameters:
			 *  {Object} rootNode
			 */
			refreshSelected : function(rootNode) {
				var self = this, st = this.getTree(), ids = this.getIDs(), allIds = [];
				// Default to refreshing the entire tree
				if(rootNode == null) rootNode = st;

				for(var idx in ids) {
					$(st.getTreeNodeByIdx(idx)).addClass('selected').attr('selected', 'selected');
				}

				jQuery(rootNode).find('li').each(function() {
					var id = parseInt(this.id.replace('record-',''), 10);
					if(id) allIds.push(id);
					
					// Disable the nodes while the ajax request is being processed
					$(this).removeClass('nodelete').addClass('treeloading');
				});

				// Post to the server to ask which pages can have this batch action applied
				var applicablePagesURL = this.find(':input[name=Action]').val() + '/applicablepages/?csvIDs=' + allIds.join(',');
				jQuery.getJSON(applicablePagesURL, function(applicableIDs) {
					var i;
					var applicableIDMap = {};
					for(i=0;i<applicableIDs.length;i++) applicableIDMap[applicableIDs[i]] = true;

					// Set a CSS class on each tree node indicating which can be batch-actioned and which can't
					jQuery(rootNode).find('li').each(function() {
						$(this).removeClass('treeloading');

						var id = parseInt(this.id.replace('record-',''), 10);
						if(id) {
							if(applicableIDMap[id] === true) {
								$(this).removeClass('nodelete');
							} else {
								// De-select the node if it's non-applicable
								$(this).removeClass('selected').addClass('nodelete');
							}
						}
					});
					
					self.serializeFromTree();
				});
			},
			
			/**
			 * Function: serializeFromTree
			 * 
			 * Returns:
			 *  (boolean)
			 */
			serializeFromTree: function() {
				var tree = this.getTree(), ids = [];
				
				// find all explicitly selected IDs
				$(tree).find('li.selected').each(function() {
					ids.push(tree.getIdxOf(this));
					// // find implicitly selected children
					// $(this).find('li').each(function() {
					// 	ids.push(tree.getIdxOf(this));
					// });
				});
			
				// if no IDs are selected, stop here. This is an implict way for the
				// callback to cancel the actions
				if(!ids || !ids.length) return false;

				// write IDs to the hidden field
				this.setIDs(ids);
				
				return true;
			},
			
			/**
			 * Function: setIDS
			 *  
			 * Parameters:
			 *  {Array} ids
			 */
			setIDs: function(ids) {
				this.find(':input[name=csvIDs]').val(ids.join(','));
			},
			
			/**
			 * Function: getIDS
			 * 
			 * Returns:
			 *  {Array}
			 */
			getIDs: function() {
				return this.find(':input[name=csvIDs]').val().split(',');
			},
		
			/**
			 * Function: onsubmit
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			onsubmit: function(e) {
				var ids = this.getIDs();
				var tree = this.getTree();
				
				// if no nodes are selected, return with an error
				if(!ids || !ids.length) {
					alert(ss.i18n._t('CMSMAIN.SELECTONEPAGE'));
					return false;
				}
				
				// apply callback, which might modify the IDs
				var type = this.find(':input[name=Action]').val();
				if(this.getActions()[type]) ids = this.getActions()[type].apply(this, [ids]);
			
				// write (possibly modified) IDs back into to the hidden field
				this.setIDs(ids);
				
				// Reset failure states
				$(tree).find('li').removeClass('failed');
			
				var button = this.find(':submit:first');
				button.addClass('loading');
			
				jQuery.ajax({
					// don't use original form url
					url: type,
					type: 'POST',
					data: this.serializeArray(),
					complete: function(xmlhttp, status) {
						button.removeClass('loading');
					
						// status message
						var msg = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
						statusMessage(msg, (status == 'success') ? 'good' : 'bad');
					},
					success: function(data, status) {
						var id;
						
						// TODO This should use a more common serialization in a new tree library
						if(data.modified) {
							for(id in data.modified) {
								tree.setNodeTitle(id, data.modified[id]['TreeTitle']);
							}
						}
						if(data.deleted) {
							for(id in data.deleted) {
								var node = tree.getTreeNodeByIdx(id);
								if(node && node.parentTreeNode)	node.parentTreeNode.removeTreeNode(node);
							}
						}
						if(data.error) {
							for(id in data.error) {
								var node = tree.getTreeNodeByIdx(id);
								$(node).addClass('failed');
							}
						}
						
						// Deselect all nodes
						$(tree).find('li').removeClass('selected');
					
						// reset selection state
						// TODO Should unselect all selected nodes as well
						// jQuery(tree).removeClass('multiselect');
					
						// Check if current page still exists, and refresh it.
						// Otherwise remove the current form
						var selectedNode = tree.firstSelected();
						if(selectedNode) {
							var selectedNodeId = tree.getIdxOf(selectedNode);
							if(data.modified[selectedNode.getIdx()]) {
								// only if the current page was modified
								selectedNode.selectTreeNode();
							} else if(data.deleted[selectedNode.getIdx()]) {
								jQuery('#Form_EditForm').entwine('ss').removeForm();
							}
						} else {
							jQuery('#Form_EditForm').entwine('ss').removeForm();
						}
					
						// close panel
						// TODO Coupling with tabs
						// jQuery('#TreeActions').tabs('select', -1);
					},
					dataType: 'json'
				});
			
				return false;
			},

			/**
			 * Function: _multiselectTransform
			 * 
			 * Todo:
			 *  This is simulating MultiselectTree functionality, and shouldn't be necessary.
			 */
			_multiselectTransform : function() {
				// make tree selectable
				jQuery(this.getTree()).addClass('multiselect');

				// auto-select the current node
				var node = this.getTree().firstSelected();
				if(node){
					$(node).removeClass('current').addClass('selected');	
					node.open();	

					// Open all existing children, which might trigger further
					// ajaxExansion calls to ensure all nodes are selectable
					var children = $(node).find('li').each(function() {
						this.open();
					});
				}
			},
		
			/**
			 * Function: _treeSelectionChanged
			 * 
			 * Only triggers if the field is considered 'active'.
			 * 
			 * Todo:
			 *  Most of this is basically simulating broken behaviour of the MultiselectTree mixin,
			 *  and should be removed.
			 */
			_treeSelectionChanged: function(node) {
				if(!this._isActive()) return;
			
				if(node.selected) {
					$(node).removeClass('selected').attr('selected', false);
				} else {
					// Select node
					$(node).addClass('selected').attr('selected', true);
				
					// Open node in order to allow proper selection of children
					if($(node).hasClass('unexpanded')) {
						node.open();
					}

					// Open all existing children, which might trigger further
					// ajaxExansion calls to ensure all nodes are selectable
					var children = $(node).find('li').each(function() {
						this.open();
					});
				}
				
				this.serializeFromTree();
			}
		});
	});
	
	/**
	 * Class: #Form_BatchActionsForm :select[name=Action]
	 */
	$('#Form_BatchActionsForm select[name=Action]').entwine({
		
		/**
		 * Function: onchange
		 * 
		 * Parameters:
		 *  (Event) e
		 */
		onchange: function(e) {
			$(e.target.form).entwine('ss').refreshSelected();
		}
	});
	
	$(document).ready(function() {
		/**
		 * Publish selected pages action
		 */
		$('#Form_BatchActionsForm').entwine('ss').register('admin/batchactions/publish', function(ids) {
			var confirmed = confirm(
				"You have " + ids.length + " pages selected.\n\n"
				+ "Do your really want to publish?"
			);
			return (confirmed) ? ids : false;
		});
		
		/**
		 * Unpublish selected pages action
		 */
		$('#Form_BatchActionsForm').entwine('ss').register('admin/batchactions/unpublish', function(ids) {
			var confirmed = confirm(
				"You have " + ids.length + " pages selected.\n\n"
				+ "Do your really want to unpublish?"
			);
			return (confirmed) ? ids : false;
		});
		
		/**
		 * Delete selected pages action
		 */
		$('#Form_BatchActionsForm').entwine('ss').register('admin/batchactions/delete', function(ids) {
			var confirmed = confirm(
				"You have " + ids.length + " pages selected.\n\n"
				+ "Do your really want to delete?"
			);
			return (confirmed) ? ids : false;
		});
		
		/**
		 * Delete selected pages from live action 
		 */
		$('#Form_BatchActionsForm').entwine('ss').register('admin/batchactions/deletefromlive', function(ids) {
			var confirmed = confirm(
				"You have " + ids.length + " pages selected.\n\n"
				+ "Do your really want to delete these pages from live?"
			);
			return (confirmed) ? ids : false;
		});
	});
	
})(jQuery);