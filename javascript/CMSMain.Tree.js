(function($) {

	$.entwine('ss.tree', function($){
		$('.cms-tree').entwine({
			getTreeConfig: function() {
				var self = this, config = this._super(), hints = this.getHints();
				config.plugins.push('contextmenu');
				config.contextmenu = {
					'items': function(node) {
						
						var menuitems = {
							'edit': {
								'label': ss.i18n._t('Tree.EditPage'),
								'action': function(obj) {
									$('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(
										self.data('urlEditpage'), obj.data('id')
									));
								}
							}
						};

						// Add "show as list"
						if(!node.hasClass('nochildren')) {
							menuitems['showaslist'] = {
								'label': ss.i18n._t('Tree.ShowAsList'),
								'action': function(obj) {
									$('.cms-container').entwine('.ss').loadPanel(
										self.data('urlListview') + '&ParentID=' + obj.data('id'),
										null,
										// Default to list view tab
										{tabState: {'pages-controller-cms-content': {'tabSelector': '.content-listview'}}}
									);
								}
							};
						}
						
						// Build a list for allowed children as submenu entries
						var pagetype = node.data('pagetype'),
							id = node.data('id'),
							disallowedChildren = hints[pagetype].disallowedChildren,
							allowedChildren = $.extend(true, {}, hints['All']), // clone
							disallowedClass,
							menuAllowedChildren = {},
							hasAllowedChildren = false;

						// Filter allowed
						if(disallowedChildren) {
							for(var i=0; i<disallowedChildren.length; i++) {
								disallowedClass = disallowedChildren[i];
								if(allowedChildren[disallowedClass]) {
									delete allowedChildren[disallowedClass];
							}
							}
						}

						// Convert to menu entries
						$.each(allowedChildren, function(klass, klassData){
							hasAllowedChildren = true;
							menuAllowedChildren["allowedchildren-" + klass ] = {
								'label': '<span class="jstree-pageicon"></span>' + klassData.title,
								'_class': 'class-' + klass,
								'action': function(obj) {
									$('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(
									self.data('urlAddpage'), id, klass
									));
								}
							};
						});
						
						if(hasAllowedChildren) {
							menuitems['addsubpage'] = {
								'label': ss.i18n._t('Tree.AddSubPage'),
								'submenu': menuAllowedChildren
							};
						}				

						menuitems['duplicate'] = {
							'label': ss.i18n._t('Tree.Duplicate'),
							'submenu': [
								{
									'label': ss.i18n._t('Tree.ThisPageOnly'),
									'action': function(obj) {
										$('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(
											self.data('urlDuplicate'), obj.data('id')
										));
									}
								},{
									'label': ss.i18n._t('Tree.ThisPageAndSubpages'),
									'action': function(obj) {
										$('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(
											self.data('urlDuplicatewithchildren'), obj.data('id')
										));
									}
								}
							]									
						};

						return menuitems;
					} 
				};
				return config;
			}
		});
	});

}(jQuery));
