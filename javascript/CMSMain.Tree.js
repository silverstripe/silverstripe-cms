(function($) {

	$.entwine('ss.tree', function($){
		$('.cms-tree').entwine({
			fromDocument: {
				'oncontext_show.vakata': function(e){
					this.adjustContextClass();
				}
			},
			/*
			 * Add and remove classes from context menus to allow for
			 * adjusting the display
			 */
			adjustContextClass: function(){
				var menus = $('#vakata-contextmenu').find("ul ul");

				menus.each(function(i){
					var col = "1",
						count = $(menus[i]).find('li').length;

					//Assign columns to menus over 10 items long
					if(count > 20){
						col = "3";
					}else if(count > 10){
						col = "2";
					}

					$(menus[i]).addClass('col-' + col).removeClass('right');

					//Remove "right" class that jstree adds on mouseenter
					$(menus[i]).find('li').on("mouseenter", function (e) {
						$(this).parent('ul').removeClass("right");
					});
				});
			},
			getTreeConfig: function() {
				var self = this, config = this._super(), hints = this.getHints();
				config.plugins.push('contextmenu');
				config.contextmenu = {
					'items': function(node) {

						var menuitems = {
								'edit': {
									'label': ss.i18n._t('Tree.EditPage', 'Edit page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
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
							disallowedChildren = (typeof hints[pagetype] != 'undefined') ? hints[pagetype].disallowedChildren : null,
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
									$('.cms-container').entwine('.ss').loadPanel(
										$.path.addSearchParams(
											ss.i18n.sprintf(self.data('urlAddpage'), id, klass),
											self.data('extraParams')
										)
									);
								}
							};
						});

						if(hasAllowedChildren) {
							menuitems['addsubpage'] = {
									'label': ss.i18n._t('Tree.AddSubPage', 'Add page under this page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
									'submenu': menuAllowedChildren
								};
						}

						menuitems['duplicate'] = {
							'label': ss.i18n._t('Tree.Duplicate'),
							'submenu': [
								{
									'label': ss.i18n._t('Tree.ThisPageOnly'),
									'action': function(obj) {
										$('.cms-container').entwine('.ss').loadPanel(
											$.path.addSearchParams(
												ss.i18n.sprintf(self.data('urlDuplicate'), obj.data('id')),
												self.data('extraParams')
											)
										);
									}
								},{
									'label': ss.i18n._t('Tree.ThisPageAndSubpages'),
									'action': function(obj) {
										$('.cms-container').entwine('.ss').loadPanel(
											$.path.addSearchParams(
												ss.i18n.sprintf(self.data('urlDuplicatewithchildren'), obj.data('id')),
												self.data('extraParams')
											)
										);
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

		// Scroll tree down to context of the current page, if it isn't
		// already visible
		$('.cms-tree a.jstree-clicked').entwine({
			onmatch: function(){
				var self = this,
					panel = self.parents('.cms-panel-content'),
					scrollTo;

				if(self.offset().top < 0 ||
					self.offset().top > panel.height() - self.height()) {
					// Current scroll top + our current offset top is our
					// position in the panel
					scrollTo = panel.scrollTop() + self.offset().top
								+ (panel.height() / 2);

					panel.animate({
						scrollTop: scrollTo
					}, 'slow');
				}
			}
		});
	});

}(jQuery));
