(function($) {

	$.entwine('ss.tree', function($){
		$('.cms-tree').entwine({
			getTreeConfig: function() {
				var self = this, config = this._super(), hints = this.getHints();
				config.plugins.push('contextmenu');
				config.contextmenu = {
					'items': function(node) {
						
						// Build a list for allowed children as submenu entries
						var id = node.data('id');

						var allowedChildrenClasses = node.find('>a .item').data('allowedchildren');
						var allowedChildren = new Object;
						var hasAllowedChildren = false;
						$.each(allowedChildrenClasses, function(klass, title) {
							hasAllowedChildren = true;
							allowedChildren["allowedchildren-" + klass ] = {
								'label': '<span class="jstree-pageicon"></span>' + title,
								'_class': 'class-' + klass,
								'action': function(obj) {
									$('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(
										self.data('urlAddpage'), id, klass
									));
								}
							};
						});
						var menuitems = 
							{
								'edit': {
									'label': ss.i18n._t('Tree.EditPage', 'Edit page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
									'action': function(obj) {
										$('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(
											self.data('urlEditpage'), obj.data('id')
										));
									}
								}
							};
						// Test if there are any allowed Children and thus the possibility of adding some 
						if(hasAllowedChildren) {
							menuitems['addsubpage'] = {
									'label': ss.i18n._t('Tree.AddSubPage', 'Add page under this page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
									'submenu': allowedChildren
								};
						}				
						return menuitems;
					} 
				};
				return config;
			}
		});
	});

}(jQuery));
