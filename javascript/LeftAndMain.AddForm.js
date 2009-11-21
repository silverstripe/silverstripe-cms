(function($) {
	$.concrete('ss', function($){
		/**
		 * @class Simple form with a page type dropdown
		 * which creates a new page through #Form_EditForm and adds a new tree node.
		 * @name ss.Form_AddForm
		 * @requires ss.i18n
		 * @requires ss.Form_EditForm
		 */
		$('#Form_AddForm').concrete(/** @lends ss.Form_AddForm */{
			/**
			 * @type DOMElement
			 */
			Tree: null,
	
			/**
			 * @type Array Internal counter to create unique page identifiers prior to ajax saving
			 */
			NewPages: [],
	
			onmatch: function() {
				var self = this;
		
				Observable.applyTo(this[0]);
		
				var tree = jQuery('#sitetree')[0];
				this.setTree(tree);
				jQuery(tree).bind('selectionchanged', function(e, data) {self.treeSelectionChanged(e, data);});
		
				this.find(':input[name=PageType]').bind('change', this.typeDropdownChanged);
				
				this._super();
			},
	
			onsubmit: function(e) {
				var newPages = this.getNewPages();
				var tree = this.getTree();
				var parentID = (tree.firstSelected()) ? tree.getIdxOf(tree.firstSelected()) : 0;

				// TODO: Remove 'new-' code http://open.silverstripe.com/ticket/875
				if(parentID && parentID.substr(0,3) == 'new') {
					alert(ss.i18n._t('CMSMAIN.WARNINGSAVEPAGESBEFOREADDING'));
				}
		
				if(tree.firstSelected() && jQuery(tree.firstSelected()).hasClass("nochildren")) {
					alert(ss.i18n._t('CMSMAIN.CANTADDCHILDREN') );
				} 
		
				// Optionally initalize the new pages tracker
				if(!newPages[parentID] ) newPages[parentID] = 1;

				// default to first button
				var button = jQuery(this).find(':submit:first');
				button.addClass('loading');
		
				// collect data and submit the form
				var data = jQuery(this).serializeArray();
				data.push({name:'Suffix',value:newPages[parentID]++});
				data.push({name:button.attr('name'),value:button.val()});
				// TODO Should be set by hiddenfield already
				jQuery('#Form_EditForm').concrete('ss').loadForm(
					jQuery(this).attr('action'),
					function() {
						button.removeClass('loading');
					},
					{type: 'POST', data: data}
				);
		
				this.setNewPages(newPages);

				return false;
			},

			treeSelectionChanged : function(e, data) {
			  var selectedNode = data.node;
	  
				if(selectedNode.hints && selectedNode.hints.defaultChild) {
					this.find(':input[name=PageType]').val(selectedNode.hints.defaultChild);
				}
		
				var parentID = this.getTree().getIdxOf(selectedNode);
				this.find(':input[name=ParentID]').val(parentID ? parentID : 0);
			},

			typeDropdownChanged : function() {
			  var tree = this.getTree();
	  
				// Don't do anything if we're already on an appropriate node
				var sel = tree.firstSelected();
				if(sel && sel.hints && sel.hints.allowedChildren) {
					var allowed = sel.hints.allowedChildren;
					for(i=0;i<allowed.length;i++) {
						if(allowed[i] == this.value) return;
					}
				}

				// Otherwise move to the default parent for that.
				if(siteTreeHints && siteTreeHints[this.value] ) {
					var newNode = tree.getTreeNodeByIdx(siteTreeHints[this.value].defaultParent);
					if(newNode) tree.changeCurrentTo(newNode);
				}
			}
		});
	});
}(jQuery));