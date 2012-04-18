(function($) {

	$.entwine('ss', function($){
		$('.cms-tree').entwine({
			getTreeConfig: function() {
				var config = this._super();
				var hints = this.getHints();
				config.plugins.push('contextmenu');
				config.contextmenu = {
					'items': function(node) {
						// Build a list for allowed children as submenu entries
						var ssclass = node.data('ssclass');
						var id = node.data('id');

						var allowedChildren = new Object;
						$(hints[ssclass].allowedChildren).each(
							function(key, val){
								allowedChildren["allowedchildren-" + key ] = {
									'label': '<span class="jstree-pageicon"></span>' + val.ssname,
									'_class': 'class-' + val.ssclass,
									'action': function(obj) {
										// TODO Fix hardcoding of link
										$('.cms-container').loadPanel('admin/pages/add/?ParentID=' + id + '&Type=' + val.ssclass);
									}
								};
							}
						);
						var menuitems = 
							{
								'edit': {
									'label': ss.i18n._t('Tree.EditPage'),
									'action': function(obj) {
										// TODO Fix hardcoding of link
										$('.cms-container').loadPanel('admin/pages/add/show/' + obj.data('id'));
									}
								}
							};
						// Test if there are any allowed Children and thus the possibility of adding some 
						if(allowedChildren.hasOwnProperty('allowedchildren-0')) {
							menuitems['addsubpage'] = {
									'label': ss.i18n._t('Tree.AddSubPage'),
									'action': function(obj) {
										// TODO Fix hardcoding of link
										$('.cms-container').loadPanel('admin/pages/add/?ParentID=' + obj.data('id'));
									},
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