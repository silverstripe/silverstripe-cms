/**
 * @type jquery.layout Global variable so layout state management
 * can pick it up.
 */
var ss_MainLayout;

(function($) {
	$('body.CMSMain').concrete('ss', function($){return{

		/**
		 * Reference to jQuery.layout element
		 */
		MainLayout: null,
		
		onmatch: function() {
			var self = this;

			// Layout
			ss_MainLayout = this._setupLayout();
			this.setMainLayout(ss_MainLayout);
			layoutState.options.keys = "west.size,west.isClosed";
			$(window).unload(function(){ console.debug(layoutState);layoutState.save('ss_MainLayout') }); 
			
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
			var savedLayoutSettings = layoutState.load('ss_MainLayout');
			var layoutSettings = jQuery.extend({
				defaults: {
					// TODO Reactivate once we have localized values
					togglerTip_open: '',
					togglerTip_closed: '',
					resizerTip: '',
					sliderTip: '',
					onresize: function() {self._resizeChildren();},
					onopen: function() {self._resizeChildren();}
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
			}, savedLayoutSettings);
			var layout = $('body').layout(layoutSettings);
			
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
	
	/**
	 * Control the site tree filter.
	 * Toggles search form fields based on a dropdown selection,
	 * similar to "Smart Search" criteria in iTunes.
	 */
	$('#Form_SearchTreeForm').concrete('ss', function($) {return{
		
		/**
		 * @type DOMElement
		 */
		SelectEl: null,
		
		onmatch: function() {
			var self = this;
			
			// TODO Cant bind to onsubmit/onreset directly because of IE6
			this.bind('submit', function(e) {return self._submitForm(e);});
			this.bind('reset', function(e) {return self._resetForm(e);});

			// only the first field should be visible by default
			this.find('.field').not(':first').hide();

			// generate the field dropdown
			this.setSelectEl($('<select name="options" class="options"></select>')
				.appendTo(this.find('fieldset:first'))
				.bind('change', function(e) {self._addField(e);})
			);
			
			this._setOptions();
			
		},
		
		_setOptions: function() {
			var self = this;
			
			// reset existing elements
			self.SelectEl().find('option').remove();
			
			// add default option
			// TODO i18n
			$('<option value="0">Add Criteria</option>').appendTo(self.SelectEl())
			
			// populate dropdown values from existing fields
			this.find('.field').each(function() {
				$('<option />').appendTo(self.SelectEl())
					.val(this.id)
					.text($(this).find('label').text());
			});
		},
		
		_submitForm: function(e) {
			var self = this;
			var data = [];
			
			// convert from jQuery object literals to hash map
			$(this.serializeArray()).each(function(i, el) {
				data[el.name] = el.value;
			});
			
			// Set new URL
			$('#sitetree')[0].setCustomURL(this.attr('action') + '&action_getfilteredsubtree=1', data);

			// Disable checkbox tree controls that currently don't work with search.
			// @todo: Make them work together
			if ($('#sitetree')[0].isDraggable) $('#sitetree')[0].stopBeingDraggable();
			this.find('.checkboxAboveTree :checkbox').val(false).attr('disabled', true);
			
			// disable buttons to avoid multiple submission
			//this.find(':submit').attr('disabled', true);
			
			this.find(':submit[name=action_getfilteredsubtree]').addClass('loading');
			
			this._reloadSitetree();
			
			return false;
		},
		
		_resetForm: function(e) {
			this.find('.field :input').clearFields().not(':first').hide();
			
			// Reset URL to default
			$('#sitetree')[0].clearCustomURL();

			// Enable checkbox tree controls
			this.find('.checkboxAboveTree :checkbox').attr('disabled', 'false');

			// reset all options, some of the might be removed
			this._setOptions();
			
			this._reloadSitetree();
			
			return false;
		},
		
		_addField: function(e) {
			var $select = $(e.target);
			// show formfield matching the option
			this.find('#' + $select.val()).show();
			
			// remove option from dropdown, each field should just exist once
			this.find('option[value=' + $select.val() + ']').remove();
			
			// jump back to default entry
			$select.val(0);
			
			return false;
		},
		
		_reloadSitetree: function() {
			var self = this;
			
			$('#sitetree')[0].reload({
				onSuccess :  function(response) {
					self.find(':submit').attr('disabled', false).removeClass('loading');
					self.find('.checkboxAboveTree :checkbox').attr('disabled', 'true');
					statusMessage('Filtered tree','good');
				},
				onFailure : function(response) {
					self.find(':submit').attr('disabled', false).removeClass('loading');
					self.find('.checkboxAboveTree :checkbox').attr('disabled', 'true');
					errorMessage('Could not filter site tree<br />' + response.responseText);
				}
			});
		}
	}});


})(jQuery);