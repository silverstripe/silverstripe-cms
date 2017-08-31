import $ from 'jquery';
import i18n from 'i18n';

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
						edit: {
							'label': (node.hasClass('edit-disabled')) ?
								 i18n._t('CMS.EditPage', 'Edit page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree')
								 : i18n._t('CMS.ViewPage', 'View page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
							'action': function(obj) {
								$('.cms-container').entwine('.ss').loadPanel(i18n.sprintf(
									self.data('urlEditpage'), obj.data('id')
								));
							}
						}
					};

					// Add "show as list"
					if(!node.hasClass('nochildren')) {
						menuitems['showaslist'] = {
							'label': i18n._t('CMS.ShowAsList'),
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
						allowedChildren = node.find('>a .item').data('allowedchildren'),
						menuAllowedChildren = {},
						hasAllowedChildren = false;

					// Convert to menu entries
					$.each(allowedChildren, function(klass, title){
						hasAllowedChildren = true;
						menuAllowedChildren["allowedchildren-" + klass ] = {
							'label': '<span class="jstree-pageicon"></span>' + title,
							'_class': 'class-' + klass.replace(/[^a-zA-Z0-9\-_:.]+/g, '_'),
							'action': function(obj) {
								$('.cms-container').entwine('.ss').loadPanel(
									$.path.addSearchParams(
										i18n.sprintf(self.data('urlAddpage'), id, klass),
										self.data('extraParams')
									)
								);
							}
						};
					});

					if(hasAllowedChildren) {
						menuitems['addsubpage'] = {
								'label': i18n._t('CMS.AddSubPage', 'Add page under this page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
								'submenu': menuAllowedChildren
							};
					}

					if (!node.hasClass('edit-disabled')) {
						menuitems['duplicate'] = {
							'label':   i18n._t('CMS.Duplicate'),
							'submenu': [
								{
									'label':  i18n._t('CMS.ThisPageOnly'),
									'action': function (obj) {
										$('.cms-container').entwine('.ss').loadPanel(
											$.path.addSearchParams(
												i18n.sprintf(self.data('urlDuplicate'), obj.data('id')),
												self.data('extraParams')
											)
										);
									}
								}, {
									'label':  i18n._t('CMS.ThisPageAndSubpages'),
									'action': function (obj) {
										$('.cms-container').entwine('.ss').loadPanel(
											$.path.addSearchParams(
												i18n.sprintf(self.data('urlDuplicatewithchildren'), obj.data('id')),
												self.data('extraParams')
											)
										);
									}
								}
							]
						};
					}

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

	// Clear filters button
	$('.cms-tree-filtered .clear-filter').entwine({
		onclick: function () {
			window.location = location.protocol + '//' + location.host + location.pathname;
		}
	});
});
