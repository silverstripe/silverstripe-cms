var outerLayout;

(function($) {
	$('body.CMSMain').concrete({ss:{cmsMain:{
		mainLayout: null,
		
		onmatch: function() {
			this.mainLayout = this.ss().cmsMain()._setupLayout();
		},
		
		/**
		 * Initialize jQuery layout manager with the following panes:
		 * - east: Tree, Page Version History, Site Reports
		 * - center: Form
		 * - west: "Insert Image", "Insert Link", "Insert Flash" panes
		 * - north: CMS area menu bar
		 * - south: "Page view", "profile" and "logout" links
		 */
		_setupLayout: function() {
			// layout containing the tree, CMS menu, the main form etc.
			var layout = $('body').layout({
				defaults: {
					// TODO Reactivate once we have localized values
					togglerTip_open: '',
					togglerTip_closed: '',
					resizerTip: '',
					sliderTip: ''
				},
				north: {
					slidable: false,
					resizable: false,
					size: 35,
					togglerLength_open: 0
				},
				south: {
					slidable: false,
					resizable: false,
					size: 20,
					togglerLength_open: 0
				},
				east: {
					initClosed: true,
					fxName: "none"
				},
				west: {
					size: 250,
					onresize: function () { $("#treepanes").accordion("resize"); },
					onopen: function () { $("#treepanes").accordion("resize"); },
					fxName: "none"
				},
				center: {}
			});
			
			// Adjust tree accordion etc. in left panel to work correctly
			// with jQuery.layout (see http://layout.jquery-dev.net/tips.html#Widget_Accordion)
			this.find("#treepanes").accordion({
				fillSpace: true,
				animated: false
			});
			
			return layout;
		}
	}}});
	
	$('#Form_EditForm').concrete({
		
	})
})(jQuery);