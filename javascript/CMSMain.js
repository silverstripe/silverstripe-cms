/**
 * File: CMSMain.js
 */
(function($) {
	$.entwine('ss', function($){
	
		/**
		 * Class: #contentPanel form
		 * 
		 * All forms in the right content panel should have closeable jQuery UI style titles.
		 */
		// TODO Change to modal dialog, jQuery UI update somehow messed up the button positioning,
		// they're no longer clickable
		// $('#contentPanel form').entwine({
		// 	// Constructor: onmatch
		// 	onmatch: function() {
		// 	  // Style as title bar
		// 		this.find(':header:first').titlebar({
		// 			closeButton:true
		// 		});
		// 		// The close button should close the east panel of the layout
		// 		this.find(':header:first .ui-dialog-titlebar-close').bind('click', function(e) {
		// 			$('body.CMSMain').entwine('ss').getMainLayout().close('east');
		// 			return false;
		// 		});
		// 	
		// 		this._super();
		// 	}
		// });
	
		/**
		 * Class: #Form_SearchTreeForm
		 * 
		 * Control the site tree filter.
		 * Toggles search form fields based on a dropdown selection,
		 * similar to "Smart Search" criteria in iTunes.
		 */
		$('#Form_SearchTreeForm').entwine({
			/**
			 * Variable: SelectEl
			 * {DOMElement}
			 */
			SelectEl: null,
	
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;

				// only the first field should be visible by default
				this.find('.field').not('.show-default').hide();

				// generate the field dropdown
				this.setSelectEl($('<select name="options" class="options"></select>')
					.appendTo(this.find('fieldset:first'))
					.bind('change', function(e) {self._addField(e);})
				);

				this._setOptions();
				
				// special case: we can't use CMSSiteTreeFilter together with other options
				this.find('select[name=FilterClass]').change(function(e) {
					var others = self.find('.field').not($(this).parents('.field')).find(':input,select');
					if(e.target.value == 'CMSSiteTreeFilter_Search') others.removeAttr('disabled');
					else others.attr('disabled','disabled');
				});
				
				// Reset binding through entwine doesn't work in IE
				this.bind('reset', function(e) {
					self._onreset(e);
				});
		
				this._super();
			},
	
			/**
			 * Function: _setOptions
			 */
			_setOptions: function() {
				var self = this;
		
				// reset existing elements
				self.getSelectEl().find('option').remove();
		
				// add default option
				// TODO i18n
				jQuery(
					'<option value="0">' + 
					ss.i18n._t('CMSMAIN.AddSearchCriteria') + 
					'</option>'
				).appendTo(self.getSelectEl());
		
				// populate dropdown values from existing fields
				this.find('.field').not(':visible').each(function() {
					$('<option />').appendTo(self.getSelectEl())
						.val(this.id)
						.text($(this).find('label').text());
				});
			},
	
			/**
			 * Function: onsubmit
			 * 
			 * Filter tree based on selected criteria.
			 */
			onsubmit: function(e) {
				var self = this;
				var data = [];
		
				// convert from jQuery object literals to hash map
				$(this.serializeArray()).each(function(i, el) {
					data[el.name] = el.value;
				});
		
				// Disable checkbox tree controls that currently don't work with search.
				this.find('.checkboxAboveTree :checkbox').attr('disabled', 'disabled');
				
				// disable buttons to avoid multiple submission
				//this.find(':submit').attr('disabled', true);
		
				this.find(':submit[name=action_doSearchTree]').addClass('loading');
		
				this._reloadSitetree(this.serializeArray());

				return false;
			},
		
			/**
			 * Function: onreset
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			_onreset: function(e) {
				this.find('.field :input').clearFields();
				this.find('.field').not('.show-default').hide();
		
				// Enable checkbox tree controls
				this.find('.checkboxAboveTree :checkbox').attr('disabled', 'false');

				// reset all options, some of the might be removed
				this._setOptions();
		
				this._reloadSitetree();
		
				return false;
			},
	
			/**
			 * Function: _addField
			 * 
			 * Parameters:
			 *  (Event) e
			 */
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
	
			/**
			 * Function: _reloadSitetree
			 */
			_reloadSitetree: function(params) {
				var self = this;
		
				$('#sitetree_ul').search(
					params,
					function() {
						self.find(':submit').attr('disabled', false).removeClass('loading');
						self.find('.checkboxAboveTree :checkbox').attr('disabled', 'true');
						statusMessage('Filtered tree','good');
					},
					function() {
						self.find(':submit').attr('disabled', false).removeClass('loading');
						self.find('.checkboxAboveTree :checkbox').attr('disabled', 'true');
						errorMessage('Could not filter site tree<br />' + response.responseText);
					}
				);		
			}
		});
	
		/**
		 * Class: Form_SideReportsForm
		 * 
		 * Simple form with a page type dropdown
		 * which creates a new page through #Form_EditForm and adds a new tree node.
		 */
		$('#Form_SideReportsForm').entwine(/** @lends ss.reports_holder */{
			ReportContainer: null,
			
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;
				
				this.setReportContainer($('#SideReportsHolder'))
					
				// integrate with sitetree selection changes
				// TODO Only trigger when report is visible
				jQuery('#sitetree_ul').bind('select_node.jstree', function(e, data) {
					var node = data.rslt.obj;
					self.find(':input[name=ID]').val(node ? $(node).data('id') : null);
					self.trigger('submit');
				});
			
				// move submit button to the top
				//this.find('#ReportClass').after(this.find('.Actions'));
			
				this._super();
			},
		
			/**
			 * Function: onsubmit
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			onsubmit: function(e) {
				var self = this;
			
				// dont process if no report is selected
				var reportClass = this.find(':input[name=ReportClass]').val();
				if(!reportClass) return false;
			
				var button = this.find(':submit:first');
				button.addClass('loading');
			
				jQuery.ajax({
					url: this.attr('action'),
					data: this.serializeArray(),
					dataType: 'html',
					success: function(data, status) {
						// replace current form
						self.getReportContainer().html(data);
					},
					complete: function(xmlhttp, status) {
						button.removeClass('loading');
					}
				});
			
				return false;
			}
		});
		
		/**
		 * Class: #SideReportsHolder form
		 * 
		 * All forms loaded via ajax from the Form_SideReports dropdown.
		 */
		$("#SideReportsHolder form").entwine({
			
			/**
			 * Function: onsubmit
			 */
			onsubmit: function() {
				var self = this;

				var button = this.find(':submit:first');
				button.addClass('loading');
			
				jQuery.ajax({
					url: this.attr('action'),
					data: this.serializeArray(),
					dataType: 'html',
					success: function(data, status) {
						// replace current form
						self.html(data);
					},
					complete: function(xmlhttp, status) {
						button.removeClass('loading');
					}
				});
			
				return false;
			}
			
		});
		
		/**
		 * Register the onclick handler that loads the page into EditForm
		 */
		$("#SideReportsHolder form ul a").entwine({
			
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				if (e.button!=2) {
					var $link = $(this);
					$link.addClass('loading');
					jQuery('#Form_EditForm').entwine('ss').loadForm(
						$(this).attr('href'),
						function(e) {
							$link.removeClass('loading');
						}
					);
				}
				
				return false;
			}
		});
	
		/**
		 * Class: #Form_VersionsForm
		 * 
		 * Simple form showing versions of a specific page.
		 */
		$('#Form_VersionsForm').entwine({
			onmatch: function() {
				var self = this;
			
				// set button to be available in form submit event later on
				this.find(':submit').bind('click', function(e) {
					self.data('_clickedButton', this);
				});
				
				this.bind('submit', function(e) {
					return self._submit();
				});
			
				// integrate with sitetree selection changes
				jQuery('#sitetree_ul').bind('select_node.jstree', function(e, data) {
					var node = data.rslt.obj;
					self.find(':input[name=ID]').val(node ? $(node).data('id') : null);
					if(self.is(':visible')) self.trigger('submit');
				});
			
				// refresh when field is selected
				// TODO coupling
				$('#treepanes').bind('accordionchange', function(e, ui) {
					if($(ui.newContent).attr('id') == 'Form_VersionsForm') self.trigger('submit');
				});
			
				// submit when 'show unpublished versions' checkbox is changed
				this.find(':input[name=ShowUnpublished]').bind('change', function(e) {
					// force the refresh button, not 'compare versions'
					self.data('_clickedButton', self.find(':submit[name=action_versions]'));
					self.trigger('submit');
				});
			
				// move submit button to the top
				// this.find('#ReportClass').after(this.find('.Actions'));
			
				// links in results
				this.find('td').bind('click', function(e) {
					var td = $(this);
				
					// exclude checkboxes
					if($(e.target).is(':input')) return true;
				
					var link = $(this).siblings('.versionlink').find('a').attr('href');
					td.addClass('loading');
					jQuery('#Form_EditForm').entwine('ss').loadForm(
						link,
						function(e) {
							td.removeClass('loading');
						}
					);
					return false;
				});
			
				// compare versions action
				this.find(':submit[name=action_compareversions]').bind('click', function(e) {
					// validation: only allow selection of exactly two versions
					var versions = self.find(':input[name=Versions[]]:checked');
					if(versions.length != 2) {
						alert(ss.i18n._t(
							'CMSMain.VALIDATIONTWOVERSION',
							'Please select two versions'
						));
						return false;
					}
				
					// overloaded submission: refresh the right form instead
					self.data('_clickedButton', this);
					self._submit(true);
				
					return false;
				});
				
				this._super();
			},
		
			/**
			 * Function: _submit
			 * 
			 * Parameters:
			 *  (bool) loadEditForm - Determines if responses should show in current panel,
			 *   or in the edit form (in the case of 'compare versions').
			 */
			_submit: function(loadEditForm) {
				var self = this;
			
				// Don't submit with empty ID
				if(!this.find(':input[name=ID]').val()) return false;
			
				var $button = (self.data('_clickedButton')) ? $(self.data('_clickedButton')) : this.find(':submit:first');
				$button.addClass('loading');
			
				var data = this.serializeArray();
				data.push({name:$button.attr('name'), value: $button.val()});
			
				if(loadEditForm) {
					jQuery('#Form_EditForm').entwine('ss').loadForm(
						this.attr('action'),
						function(e) {
							$button.removeClass('loading');
						},
						{data: data, type: 'POST'}
					);
				} else {
					jQuery.ajax({
						url: this.attr('action'),
						data: data,
						dataType: 'html',
						success: function(data, status) {
							self.replaceWith(data);
						},
						complete: function(xmlhttp, status) {
							$button.removeClass('loading');
						}
					});
				}
				
				return false;
			}
		});
	});
})(jQuery);