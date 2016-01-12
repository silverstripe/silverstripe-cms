(function($) {
	/**
	 * File: CMSPageHistoryController.js
	 *
	 * Handles related interactions between the version selection form on the
	 * left hand side of the panel and the version displaying on the right
	 * hand side.
	 */
	
	$.entwine('ss', function($){

		/**
		 * Class: #Form_VersionsForm
		 *
		 * The left hand side version selection form is the main interface for
		 * users to select a version to view, or to compare two versions
		 */
		$('#Form_VersionsForm').entwine({
			/**
			 * Constructor
			 */
			onmatch: function() {
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			/**
			 * Function: submit.
			 *
			 * Submits either the compare versions form or the view single form
			 * display based on whether we have two or 1 option selected
			 *
			 * Todo:
			 *		Handle coupling to admin url
			 */
			onsubmit: function(e, d) {
				e.preventDefault();
				
				var id, self = this;
				
				id = this.find(':input[name=ID]').val();
	
				if(!id) return false;

				var button, url, selected, to, from, compare, data;
				
				compare = (this.find(":input[name=CompareMode]").is(":checked"));
				selected = this.find("table input[type=checkbox]").filter(":checked");
				
				if(compare) {
					if(selected.length != 2) return false;
					
					to = selected.eq(0).val();
					from = selected.eq(1).val();
					button = this.find(':submit[name=action_doCompare]');
					url = ss.i18n.sprintf(this.data('linkTmplCompare'), id,from,to);
				}
				else {
					to = selected.eq(0).val();
					button = this.find(':submit[name=action_doShowVersion]');
					url = ss.i18n.sprintf(this.data('linkTmplShow'), id,to);
				}
				
				$('.cms-container').loadPanel(url, '', {pjax: 'CurrentForm'});
			}
		});

		/**
		 * Class: :input[name=ShowUnpublished]
		 *
		 * Used for toggling whether to show or hide unpublished versions.
		 */
		$('#Form_VersionsForm input[name=ShowUnpublished]').entwine({
			onmatch: function() {
				this.toggle();
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			/**
			 * Event: :input[name=ShowUnpublished] change
			 *
			 * Changing the show unpublished checkbox toggles whether to show
			 * or hide the unpublished versions. Because those rows may be being
			 * compared this also ensures those rows are unselected.
			 */
			onchange: function() {
				this.toggle();
			},
			toggle: function() {
				var self = $(this);
				var form = self.parents('form');

				if(self.attr('checked')) {
					form.find('tr[data-published=false]').show();
				} else {
					form.find("tr[data-published=false]").hide()._unselect();
				}
			}
		});

		/**
		 * Class: #Form_VersionsForm tr
		 *
		 * An individual row in the versions form. Selecting the row updates
		 * the edit form depending on whether we're showing individual version
		 * information or displaying comparsion.
		 */
		$("#Form_VersionsForm tbody tr").entwine({
			
			/**
			 * Function: onclick
			 *
			 * Selects or deselects the row (if in compare mode). Will trigger
			 * an update of the edit form if either selected (in single mode)
			 * or if this is the second row selected (in compare mode)
			 */
			onclick: function(e) {
				var compare, selected;
				
				// compare mode
				compare = this.parents("form").find(':input[name=CompareMode]').attr("checked");
				selected = this.siblings(".active");
				
				if(compare && this.hasClass('active')) {
					this._unselect();
					
					return;
				}
				else if(compare) {
					// check if we have already selected more than two.
					if(selected.length > 1) {
						return alert(ss.i18n._t('ONLYSELECTTWO', 'You can only compare two versions at this time.'));
					}
				
					this._select();

					// if this is the second selected then we can compare.
					if(selected.length == 1) {
						this.parents('form').submit();
					}
						
					return;
				}
				else {
					this._select();
					selected._unselect();	
					
					this.parents("form").submit();
				}
			},
			
			/**
			 * Function: _unselect()
			 *
			 * Unselects the row from the form selection.
			 */
			_unselect: function() {
				this.removeClass('active');
				this.find(":input[type=checkbox]").attr("checked", false);
			},
			
			/**
			 * Function: _select()
			 *
			 * Selects the currently matched row in the form selection
			 */
			_select: function() {
				this.addClass('active');
				this.find(":input[type=checkbox]").attr("checked", true);
			}
			
		});
	});
})(jQuery);
