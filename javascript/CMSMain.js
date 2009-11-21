(function($) {
	$('body.CMSMain').concrete('ss', function($){return{

		/**
		 * Reference to jQuery.layout element
		 */
		MainLayout: null,
		
		onmatch: function() {
			var self = this;

			this.setMainLayout(this._setupLayout());
			
			// artificially delay the resize event 200ms
			// to avoid overlapping height changes in different onresize() methods
			$(window).resize(function () {
				var timerID = "timerCMSMainResize";
				if (window[timerID]) clearTimeout(window[timerID]);
				window[timerID] = setTimeout(function() {self._resizeChildren();}, 200);
			});

			this._resizeChildren();

			this._super();
		},
		
		_resizeChildren: function() {
			$("#treepanes", this).accordion("resize");
			$('#sitetree_and_tools', this).fitHeightToParent();
			$('#contentPanel form', this).fitHeightToParent();
			$('#contentPanel form fieldset', this).fitHeightToParent();
			$('#contentPanel form fieldset .content', this).fitHeightToParent();
			
			this._super();
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
			var self = this;
			
			// layout containing the tree, CMS menu, the main form etc.
			var layout = $('body').layout({
				defaults: {
					// TODO Reactivate once we have localized values
					togglerTip_open: '',
					togglerTip_closed: '',
					resizerTip: '',
					sliderTip: '',
					onresize: function() {self._resizeChildren();},
					onopen: function() {self._resizeChildren();},
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
				west: {
					size: 250,
					fxName: "none"
				},
				east: {
					initClosed: true,
					fxName: "none",
					size: 250
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
	}});
	
	/**
	 * CMS-specific form behaviour
	 */
	$('#Form_EditForm').concrete('ss', function($){return{
		onmatch: function() {
			// Alert the user on change of page-type - this might have implications
			// on the available form fields etc.
			this.find(':input[name=ClassName]').bind('change',
				function() {
					alert('The page type will be updated after the page is saved');
				}
			);
			
			this.find(':input[name=ParentID]')
		}
	}});
	
	/**
	 * ParentType / ParentID field combination - mostly toggling between
	 * the two radiobuttons and setting the hidden "ParentID" field
	 */
	$('#Form_EditForm_ParentType').concrete('ss', function($){return{
		onmatch : function() {
			var parentTypeRootEl = $('#Form_EditForm_ParentType_root');
			var parentTypeSubpageEl = $('#Form_EditForm_ParentType_subpage');
			if(parentTypeRootEl) {
				parentTypeRootEl.onclick = this.rootClick.bind(this);
			}
			if(parentTypeSubpageEl) {
				parentTypeSubpageEl.onclick = this.showHide;
			}
			this.showHide();
		},
		
		rootClick : function() {
			$('#Form_EditForm_ParentID').val(0);
			this.showHide();
		},
		
		showHide : function() {
			var parentTypeRootEl = $('#Form_EditForm_ParentType_root');
			if(parentTypeRootEl && parentTypeRootEl.checked) {
				$('#ParentID').hide();
			} else {
				$('#ParentID').show();
			}
		}
	}});
	
	/**
	 * Email containing the link to the archived version of the page.
	 * Visible on readonly older versions of a specific page at the moment.
	 */
	$('#Form_EditForm .Actions #Form_EditForm_action_email').concrete('ss', function($){return{
		onclick: function(e) {
			window.open(
				'mailto:?subject=' 
					+ $('input[name=ArchiveEmailSubject]', this[0].form).val() 
					+ '&body=' 
					+ $(':input[name=ArchiveEmailMessage]', this[0].form).val(), 
				'archiveemail' 
			);
			
			return false;
		}
	}});

	/**
	 * Open a printable representation of the form in a new window.
	 * Used for readonly older versions of a specific page.
	 */
	$('#Form_EditForm .Actions #Form_EditForm_action_print').concrete('ss', function($){return{
		onclick: function(e) {
			var printURL = $(this[0].form).attr('action').replace(/\?.*$/,'') 
				+ '/printable/' 
				+ $(':input[name=ID]',this[0].form).val();
			if(printURL.substr(0,7) != 'http://') printURL = $('base').attr('href') + printURL;

			window.open(printURL, 'printable');
			
			return false;
		}
	}});
	
	/**
	 * A "rollback" to a specific version needs user confirmation.
	 */
	$('#Form_EditForm .Actions #Form_EditForm_action_rollback').concrete('ss', function($){return{
		onclick: function(e) {
			// @todo i18n
			return confirm("Do you really want to copy the published content to the stage site?");
		}
	}});
	
	/**
	 * All forms in the right content panel should have closeable jQuery UI style titles.
	 */
	$('#contentPanel form').concrete('ss', function($){return{
		onmatch: function() {
		  // Style as title bar
			this.find(':header:first').titlebar({
				closeButton:true
			});
			// The close button should close the east panel of the layout
			this.find(':header:first .ui-dialog-titlebar-close').bind('click', function(e) {
				$('body.CMSMain').concrete('ss').MainLayout().close('east');
				
				return false;
			});
		}
	}});

})(jQuery);