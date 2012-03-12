(function($) {

	$.entwine('ss', function($){
	
		$('.cms-tree').entwine({
			getTreeConfig: function() {
				var config = this._super();
				config.plugins.push('contextmenu');
				config.contextmenu = {
					'items': {
						'create': null,
						"rename": null,
						"remove": null,
						"ccp": null,
						'edit': {
							'label': ss.i18n._t('Tree.EditPage'),
							'action': function(obj) {
								// TODO Fix hardcoding of link
								$('.cms-container').loadPanel('admin/page/edit/show/' + obj.data('id'));
							}
						},
						'addsubpage': {
							'label': ss.i18n._t('Tree.AddSubPage'),
							'action': function(obj) {
								// TODO Fix hardcoding of link
								$('.cms-container').loadPanel('admin/page/add/?ParentID=' + obj.data('id'));
							}
						}
					}
				};
				return config;
			}
		});
	});
}(jQuery));