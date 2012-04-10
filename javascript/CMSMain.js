/**
 * File: CMSMain.js
 */
(function($) {
	$.entwine('ss', function($){
	
		/**
		 * Class: #Form_SearchForm
		 * 
		 * Control the site tree filter.
		 * Toggles search form fields based on a dropdown selection,
		 * similar to "Smart Search" criteria in iTunes.
		 */
		$('#Form_SearchForm').entwine({
	
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;

				// Reset binding through entwine doesn't work in IE
				this.bind('reset', function(e) {
					self._onreset(e);
				});
		
				this._super();
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
		
				// TODO Disable checkbox tree controls that currently don't work with search.
				this.find('.checkboxAboveTree :checkbox').attr('disabled', 'disabled');
				
				// TODO disable buttons to avoid multiple submission
				//this.find(':submit').attr('disabled', true);
		
				this.find(':submit[name=action_doSearchTree]').addClass('loading');
				
				var params = this.serializeArray();
				this._reloadSitetree(params);
				this._reloadListview(params)

				return false;
			},
		
			/**
			 * Function: onreset
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			_onreset: function(e) {
				// TODO Enable checkbox tree controls
				this.find('.checkboxAboveTree :checkbox').attr('disabled', 'false');

				this._reloadSitetree();
		
				return false;
			},
	
			/**
			 * Function: _reloadSitetree
			 */
			_reloadSitetree: function(params) {
				var self = this;
		
				$('.cms-tree').search(
					params,
					function() {
						self.find(':submit').attr('disabled', false).removeClass('loading');
						self.find('.checkboxAboveTree :checkbox').attr('disabled', 'true');
					},
					function() {
						self.find(':submit').attr('disabled', false).removeClass('loading');
						self.find('.checkboxAboveTree :checkbox').attr('disabled', 'true');
						errorMessage('Could not filter site tree<br />' + response.responseText);
					}
				);		
			},
			
			_reloadListview: function(params){
				$('.cms-list').refresh(params);
				
			}
		});
		
		$('#cms-content-listview .cms-list').entwine({
			refresh: function(params){
				var self = this;
				
				$.ajax({
					url: this.data('url-list'),
					data: params,
					success: function(data, status, xhr) {
						self.html(data);
					},
					error: function(xhr, status, e) {
						errorMessage(e);
					}
				});
			},
			replace: function(url){
				if(window.History.enabled) {
					var container = $('.cms-container')
					container.loadPanel(url, '', {selector: '.cms-list'});
				} else {
					window.location = $.path.makeUrlAbsolute(url, $('base').attr('href'));
				}
			}
		});
		
		$('.cms-list .list-children-link').entwine({
			onclick: function(e) {
				this.closest('.cms-list').replace(this.attr('href'));
				e.preventDefault();
				return false;

			}
		});
	
		/**
		 * Class: Form_SideReportsForm
		 * 
		 * Simple form with a page type dropdown
		 * which creates a new page through .cms-edit-form and adds a new tree node.
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
				jQuery('.cms-tree').bind('select_node.jstree', function(e, data) {
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
					jQuery('.cms-content').entwine('ss').loadForm(
						$(this).attr('href'),
						null,
						function(e) {
							$link.removeClass('loading');
						}
					);
				}
				
				return false;
			}
		});
	});
})(jQuery);