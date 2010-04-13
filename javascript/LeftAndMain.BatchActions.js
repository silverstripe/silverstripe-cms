(function($) {
	$.entwine('ss', function($){
	
		/**
		 * @class Batch actions which take a bunch of selected pages,
		 * usually from the CMS tree implementation, and perform serverside
		 * callbacks on the whole set. We make the tree selectable when the jQuery.UI tab
		 * enclosing this form is opened.
		 * @name ss.Form_BatchActionsForm
		 * 
		 * Events:
		 * - register: Called before an action is added.
		 * - unregister: Called before an action is removed.
		 */
		$('#Form_BatchActionsForm').entwine(/** @lends ss.Form_BatchActionsForm */{
	
			/**
			 * @type {DOMElement}
			 */
			Tree: null,
		
			/**
			 * @type {Array} Stores all actions that can be performed on the collected IDs as
			 * function closures. This might trigger filtering of the selected IDs,
			 * a confirmation message, etc.
			 */
			Actions: [],
		
			onmatch: function() {
				var self = this;
				
				this.setTree($('#sitetree')[0]);
			
				$(this.getTree()).bind('selectionchanged', function(e, data) {
					self._treeSelectionChanged(data.node);
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
					}
				
				});
				
				this._super();
			},
		
			/**
			 * @param {String} type
			 * @param {Function} callback
			 */
			register: function(type, callback) {
				this.trigger('register', {type: type, callback: callback});
				var actions = this.getActions();
				actions[type] = callback;
				this.setActions(actions);
			},
		
			/**
			 * Remove an existing action.
			 * 
			 * @param {String} type
			 */
			unregister: function(type) {
				this.trigger('unregister', {type: type});
			
				var actions = this.getActions();
				if(actions[type]) delete actions[type];
				this.setActions(actions);
			},
		
			/**
			 * Determines if we should allow and track tree selections.
			 * 
			 * @todo Too much coupling with tabset
			 * @return boolean
			 */
			_isActive: function() {
				return $('#TreeActions-batchactions').is(':visible');
			},
		
			onsubmit: function(e) {
				var ids = [];
				var tree = this.getTree();
				// find all explicitly selected IDs
				$(tree).find('li.selected').each(function() {
					ids.push(tree.getIdxOf(this));
					// find implicitly selected children
					$(this).find('li').each(function() {
						ids.push(tree.getIdxOf(this));
					});
				});
			
				// if no nodes are selected, return with an error
				if(!ids || !ids.length) {
					alert(ss.i18n._t('CMSMAIN.SELECTONEPAGE'));
					return false;
				}
			
				// apply callback, which might modify the IDs
				var type = this.find(':input[name=Action]').val();
				if(this.getActions()[type]) ids = this.getActions()[type].apply(this, [ids]);
			
				// if no IDs are selected, stop here. This is an implict way for the
				// callback to cancel the actions
				if(!ids || !ids.length) return false;

				// write IDs to the hidden field
				this.find(':input[name=csvIDs]').val(ids.join(','));
			
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
						// TODO This should use a more common serialization in a new tree library
						if(data.modified) {
							for(var id in data.modified) {
								tree.setNodeTitle(id, data.modified[id]['TreeTitle']);
							}
						}
						if(data.deleted) {
							for(var id in data.deleted) {
								var node = tree.getTreeNodeByIdx(id);
								if(node && node.parentTreeNode)	node.parentTreeNode.removeTreeNode(node);
							}
						}
					
						// reset selection state
						// TODO Should unselect all selected nodes as well
						jQuery(tree).removeClass('multiselect');
					
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
						jQuery('#TreeActions').tabs('select', -1);
					},
					dataType: 'json'
				});
			
				return false;
			},

			/**
			 * @todo This is simulating MultiselectTree functionality, and shouldn't be necessary.
			 */
			_multiselectTransform : function() {
				// make tree selectable
				jQuery(this.getTree()).addClass('multiselect');

				// auto-select the current node
				var node = this.getTree().firstSelected();
				if(node){
					node.removeNodeClass('current');
					node.addNodeClass('selected');	
					node.open();	

					// Open all existing children, which might trigger further
					// ajaxExansion calls to ensure all nodes are selectable
					var children = $(node).find('li').each(function() {
						this.open();
					});
				}
			},
		
			/**
			 * Only triggers if the field is considered 'active'.
			 * @todo Most of this is basically simulating broken behaviour of the MultiselectTree mixin,
			 *  and should be removed.
			 */
			_treeSelectionChanged: function(node) {
				if(!this._isActive()) return;
			
				if(node.selected) {
					node.removeNodeClass('selected');
					node.selected = false;
				} else {
					// Select node
					node.addNodeClass('selected');
					node.selected = true;
				
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
			}
		});
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