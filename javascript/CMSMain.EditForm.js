/**
 * File: CMSMain.EditForm.js
 */
(function($) {
	$.entwine('ss', function($){
		/**
		 * Class: .cms-edit-form :input[name=ClassName]
		 * Alert the user on change of page-type. This might have implications
		 * on the available form fields etc.
		 */
		$('.cms-edit-form :input[name=ClassName]').entwine({
			// Function: onchange
			onchange: function() {
				alert(ss.i18n._t('CMSMAIN.ALERTCLASSNAME'));
			}
		});

		/**
		* Class: .cms-edit-form input[name=URLSegment]
		* 
		 * Input validation on the URLSegment field
		 */
		$('.cms-edit-form input[name=URLSegment]').entwine({
	
			/**
			 * Constructor: onmatch
			 */
			onmatch : function() {
				var self = this;
		
				// intercept change event, do our own writing
				this.bind('change', function(e) {
					if(!self.val()) return;
					
					self.attr('disabled', 'disabled').parents('.field:first').addClass('loading');
					var oldVal = self.val();
					self.suggest(oldVal, function(data) {
						self.removeAttr('disabled').parents('.field:first').removeClass('loading');
						var newVal = decodeURIComponent(data.value);
						self.val(newVal);
						
						if(oldVal != newVal) {
							jQuery.noticeAdd(ss.i18n._t('The URL has been changed'));
						}
					});
					
				});
				
				this._super();
			},
	
			/**
			 * Function: suggest
			 *  
			 * Return a value matching the criteria.
			 * 
			 * Parameters:
			 *  (String) val
			 *  (Function) callback
			 */
			suggest: function(val, callback) {
				$.get(
					this.parents('form:first').attr('action') + 
						'/field/URLSegment/suggest/?value=' + encodeURIComponent(this.val()),
					function(data) {
						callback.apply(this, arguments);
					}
				);
			}
		});

		/**
		 * Class: .cms-edit-form input[name=Title]
		 * 
		 * Input validation on the Title field
		 */
		$('.cms-edit-form input[name=Title]').entwine({
			// Constructor: onmatch
			onmatch : function() {
				var self = this;
		
				this.bind('change', function(e) {
					self.updatePageTitleHeading();
					self.updateURLSegment(jQuery('.cms-edit-form input[name=URLSegment]'));
					// TODO We should really user-confirm these changes
					self.parents('form').find('input[name=MetaTitle], input[name=MenuTitle]').val(self.val());
				});
				
				this._super();
			},
			
			/**
			 * Function: updatePageTitleHeading
			 * 
			 * Update the page title heading when page title changes
			 */
			updatePageTitleHeading: function() {
				$('#page-title-heading').text(this.val());
			},
	
			/**
			 * Function: updateURLSegment
			 * 
			 * Parameters:
			 *  (DOMElement) field
			 */
			updateURLSegment: function(field) {
				if(!field || !field.length) return;
				
				// TODO The new URL value is determined asynchronously,
				// which means we need to come up with an alternative system
				// to ask user permission to change it.
		
				// TODO language/logic coupling
				var isNew = this.val().indexOf("new") == 0;
				var confirmMessage = ss.i18n._t(
					'UPDATEURL.CONFIRMSIMPLE', 
					'Do you want to update the URL from your new page title?'
				);

				// don't ask for replacement if record is considered 'new' as defined by its title
				if(isNew || confirm(confirmMessage)) {
					field.val(this.val()).trigger('change');
				}
			}
		});
	
		/**
		 * Class: .cms-edit-form .parentTypeSelector
		 * 
		 * ParentID field combination - mostly toggling between
		 * the two radiobuttons and setting the hidden "ParentID" field
		 */
		$('.cms-edit-form .parentTypeSelector').entwine({
			// Constructor: onmatch
			onmatch : function() {
				var self = this;
				this.find(':input[name=ParentType]').bind('click', function(e) {self._toggleSelection(e);});
				this._toggleSelection();
				
				this._super();
			},
	
			/**
			 * Function: _toggleSelection
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			_toggleSelection: function(e) {
				var selected = this.find(':input[name=ParentType]:checked').val();
				// reset parent id if 'root' radiobutton is selected
				if(selected == 'root') this.find(':input[name=ParentID]').val(0);
				// toggle tree dropdown based on selection
				this.find('#ParentID').toggle(selected != 'root');
			}
		});

		/**
		 * Class: .cms-edit-form #CanViewType, .cms-edit-form #CanEditType
		 * 
		 * Toggle display of group dropdown in "access" tab,
		 * based on selection of radiobuttons.
		 */
		$('.cms-edit-form #CanViewType, .cms-edit-form #CanEditType, .cms-edit-form #CanCreateTopLevelType').entwine({
			// Constructor: onmatch
			onmatch: function() {
				// TODO Decouple
				var dropdown;
				if(this.attr('id') == 'CanViewType') dropdown = $('#ViewerGroups');
				else if(this.attr('id') == 'CanEditType') dropdown = $('#EditorGroups');
				else if(this.attr('id') == 'CanCreateTopLevelType') dropdown = $('#CreateTopLevelGroups');
		
				this.find('.optionset :input').bind('change', function(e) {
					dropdown[e.target.value == 'OnlyTheseUsers' ? 'show' : 'hide']();
				});
		
				// initial state
				var currentVal = this.find('input[name=' + this.attr('id') + ']:checked').val();
				dropdown[currentVal == 'OnlyTheseUsers' ? 'show' : 'hide']();
				
				this._super();
			}
		});	

		/**
		 * Class: .cms-edit-form .Actions #Form_EditForm_action_print
		 * 
		 * Open a printable representation of the form in a new window.
		 * Used for readonly older versions of a specific page.
		 */
		$('.cms-edit-form .Actions #Form_EditForm_action_print').entwine({
			/**
			 * Function: onclick
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			onclick: function(e) {
				var printURL = $(this[0].form).attr('action').replace(/\?.*$/,'') 
					+ '/printable/' 
					+ $(':input[name=ID]',this[0].form).val();
				if(printURL.substr(0,7) != 'http://') printURL = $('base').attr('href') + printURL;

				window.open(printURL, 'printable');
	
				return false;
			}
		});

		/**
		 * Class: .cms-edit-form .Actions #Form_EditForm_action_rollback
		 * 
		 * A "rollback" to a specific version needs user confirmation.
		 */
		$('.cms-edit-form .Actions #Form_EditForm_action_rollback').entwine({
			
			/**
			 * Function: onclick
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			onclick: function(e) {
				// @todo i18n
				var form = this.parents('form:first'), version = form.find(':input[name=Version]').val(), message = '';
				if(version) {
					message = "Do you really want to roll back to version #" + version + " of this page?";
				} else {
					message = "Do you really want to copy the published content to the stage site?";
				}
				return confirm(message);
			}
		});

		/**
		 * Class: .cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked
		 *
		 * Showing the "Page location" "Parent page" chooser only when the "Sub-page underneath a parent page"
		 * radio button is selected
		 */
		$('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').entwine({

			/**
			 * Function: onclick
			 *
			 * Parameters:
			 *  (Event) e
			 */
			onclick: function(e) {
				var parentTreeDropDown = $('.cms-edit-form.CMSPageSettingsController #ParentID');

				if (e.target.id == 'Form_EditForm_ParentType_root') parentTreeDropDown.slideUp();
			  else parentTreeDropDown.slideDown();
			}
		});

		//trigger an initial change event to do the initial hiding of the element, if necessary
		if ($('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').attr('id') == 'Form_EditForm_ParentType_root') {
			$('.cms-edit-form.CMSPageSettingsController #ParentID').hide(); //quick hide on first run
		}
	});
}(jQuery));