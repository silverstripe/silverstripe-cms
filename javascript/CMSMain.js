var outerLayout;
var innerLayout;

jQuery(document).ready(function () {

	// layout containing the tree, CMS menu, the main form etc.
	outerLayout = jQuery('body').layout({
		defaults: {
			// TODO Reactivate once we have localized values
			togglerTip_open: '',
			togglerTip_closed: '',
			resizerTip: '',
			sliderTip: ''
		},
		// contains CMSMenu
		north: {
			slidable: false,
			resizable: false,
			size: 35,
			togglerLength_open: 0
		},
		// "Page view", "profile" and "logout" links
		south: {
			slidable: false,
			resizable: false,
			size: 20,
			togglerLength_open: 0
		},
		// "Insert link" etc.
		east: {
			initClosed: true,
			fxName: "none"
		},
		// Tree, page version history
		west: {
			size: 250,
			onresize: function () { jQuery("#treepanes").accordion("resize"); },
			onopen: function () { jQuery("#treepanes").accordion("resize"); },
			fxName: "none"
		},
		// Page forms
		center: {
			onresize: "innerLayout.resizeAll" 
		}
	});
	
	// Layout for the form and its buttons
	innerLayout = jQuery('#right').layout({
		center: {},
		south: {
			slidable: false,
			resizable: false,
			size: 30,
			togglerLength_open: 0
		}
		
	})

	// Adjust tree accordion etc. in left panel to work correctly
	// with jQuery.layout (see http://layout.jquery-dev.net/tips.html#Widget_Accordion)
	jQuery("#treepanes").accordion({
		fillSpace: true,
		animated: false
	});

});