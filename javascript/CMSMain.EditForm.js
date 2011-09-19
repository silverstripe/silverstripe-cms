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
			 * Property: FilterRegex
			 * Regex
			 */
			FilterRegex: /[^A-Za-z0-9-]+/,

			/**
			 * Property: ValidationMessage
			 * String
			 */
			ValidationMessage: ss.i18n._t('CMSMAIN.URLSEGMENTVALIDATION'),
			
			/**
			 * Property: MaxLength
			 * Int
			 */
			MaxLength: 50,
	
			/**
			 * Constructor: onmatch
			 */
			onmatch : function() {
				var self = this;
		
				// intercept change event, do our own writing
				this.bind('change', function(e) {
					if(!self.validate()) {
						jQuery.noticeAdd(self.getValidationMessage());
					}
					self.val(self.suggestValue(e.target.value));
					return false;
				});
				
				this._super();
			},
	
			/**
			 * Function: suggestValue
			 *  
			 * Return a value matching the criteria.
			 * 
			 * Parameters:
			 *  (String) val
			 * 
			 * Returns:
			 *  String
			 */
			suggestValue: function(val) {
				// TODO Do we want to enforce lowercasing in URLs?
				return val.substr(0, this.getMaxLength()).replace(this.getFilterRegex(), '').toLowerCase();
			},
	
			/**
			 * Function: validate
			 * 
			 * Returns:
			 *  Boolean
			 */
			validate: function() {
				return (
					this.val().length > this.getMaxLength()
					|| this.val().match(this.getFilterRegex())
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
					self.updateURLSegment(jQuery('.cms-edit-form input[name=URLSegment]'));
					// TODO We should really user-confirm these changes
					self.parents('form').find('input[name=MetaTitle], input[name=MenuTitle]').val(self.val());
				});
				
				this._super();
			},
	
			/**
			 * Function: updateURLSegment
			 * 
			 * Parameters:
			 *  (DOMElement) field
			 */
			updateURLSegment: function(field) {
				if(!field || !field.length) return;
		
				// TODO language/logic coupling
				var isNew = this.val().indexOf("new") == 0;
				var suggestion = field.entwine('ss').suggestValue(this.val());
				var confirmMessage = ss.i18n.sprintf(
					ss.i18n._t(
						'UPDATEURL.CONFIRM', 
						'Would you like me to change the URL to:\n\n' 
						+ '%s/\n\nClick Ok to change the URL, '
						+ 'click Cancel to leave it as:\n\n%s'
					),
					suggestion,
					field.val()
				);

				// don't ask for replacement if record is considered 'new' as defined by its title
				if(isNew || (suggestion != field.val() && confirm(confirmMessage))) {
					field.val(suggestion);
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
	});
}(jQuery));