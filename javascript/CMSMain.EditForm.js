(function($) {
	/**
	 * Alert the user on change of page-type - this might have implications
	 * on the available form fields etc.
	 * @name ss.EditFormClassName
	 */
	$('#Form_EditForm :input[name=ClassName]').concrete('ss', function($){
		return/** @lends ss.EditFormClassName */{
			onchange: function() {
				alert(ss.i18n._t('CMSMAIN.ALERTCLASSNAME'));
			}
		};
	});

	/**
	 * @class Input validation on the URLSegment field
	 * @name ss.EditForm.URLSegment
	 */
	$('#Form_EditForm input[name=URLSegment]').concrete('ss', function($){
		return/** @lends ss.EditForm.URLSegment */{

			FilterRegex: /[^A-Za-z0-9-]+/,

			ValidationMessage: ss.i18n._t('CMSMAIN.URLSEGMENTVALIDATION'),

			MaxLength: 50,
		
			onmatch : function() {
				var self = this;
			
				// intercept change event, do our own writing
				this.bind('change', function(e) {
					if(!self.validate()) {
						jQuery.noticeAdd(self.ValidationMessage());
					}
					self.val(self.suggestValue(e.target.value));
					return false;
				});
			},
		
			/**
			 * Return a value matching the criteria.
			 * 
			 * @param {String} val
			 * @return val
			 */
			suggestValue: function(val) {
				// TODO Do we want to enforce lowercasing in URLs?
				return val.substr(0, this.MaxLength()).replace(this.FilterRegex(), '').toLowerCase();
			},
		
			validate: function() {
				return (
					this.val().length > this.MaxLength()
					|| this.val().match(this.FilterRegex())
				);
			}
		};
	});

	/**
	 * @class Input validation on the Title field
	 * @name ss.EditForm.Title
	 */
	$('#Form_EditForm input[name=Title]').concrete('ss', function($){
		return/** @lends ss.EditForm.Title */{

			onmatch : function() {
				var self = this;
			
				this.bind('change', function(e) {
					self.updateURLSegment(jQuery('#Form_EditForm input[name=URLSegment]'));
					// TODO We should really user-confirm these changes
					self.parents('form').find('input[name=MetaTitle], input[name=MenuTitle]').val(self.val());
				});
			},
		
			updateURLSegment: function(field) {
				if(!field || !field.length) return;
			
				// TODO language/logic coupling
				var isNew = this.val().indexOf("new") == 0;
				var suggestion = field.concrete('ss').suggestValue(this.val());
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
		};
	});
	
	/**
	 * @class ParentID field combination - mostly toggling between
	 * the two radiobuttons and setting the hidden "ParentID" field
	 * @name ss.EditForm.parentTypeSelector
	 */
	$('#Form_EditForm .parentTypeSelector').concrete('ss', function($){
		return/** @lends ss.EditForm.parentTypeSelector */{
			onmatch : function() {
				var self = this;
			
				this.find(':input[name=ParentType]').bind('click', function(e) {self._toggleSelection(e);});
			
				this._toggleSelection();
			},
		
			_toggleSelection: function(e) {
				var selected = this.find(':input[name=ParentType]:checked').val();
				// reset parent id if 'root' radiobutton is selected
				if(selected == 'root') this.find(':input[name=ParentID]').val(0);
				// toggle tree dropdown based on selection
				this.find('#ParentID').toggle(selected != 'root');
			}
		};
	});

	/**
	 * @class Toggle display of group dropdown in "access" tab,
	 * based on selection of radiobuttons.
	 * @name ss.Form_EditForm.Access
	 */
	$('#Form_EditForm #CanViewType, #Form_EditForm #CanEditType').concrete('ss', function($){
		return/** @lends ss.Form_EditForm.Access */{
			onmatch: function() {
				// TODO Decouple
				var dropdown;
				if(this.attr('id') == 'CanViewType') dropdown = $('#ViewerGroups');
				else if(this.attr('id') == 'CanEditType') dropdown = $('#EditorGroups');
			
				this.find('.optionset :input').bind('change', function(e) {
					dropdown.toggle(e.target.value == 'OnlyTheseUsers');
				});
			
				// initial state
				var currentVal = this.find('input[name=' + this.attr('id') + ']:checked').val();
				dropdown.toggle(currentVal == 'OnlyTheseUsers');
			}
		};
	});	

	/**
	 * @class Email containing the link to the archived version of the page.
	 * Visible on readonly older versions of a specific page at the moment.
	 * @name ss.Form_EditForm_action_email
	 */
	$('#Form_EditForm .Actions #Form_EditForm_action_email').concrete('ss', function($){
		return/** @lends ss.Form_EditForm_action_email */{
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
		};
	});

	/**
	 * @class Open a printable representation of the form in a new window.
	 * Used for readonly older versions of a specific page.
	 * @name ss.Form_EditForm_action_print
	 */
	$('#Form_EditForm .Actions #Form_EditForm_action_print').concrete('ss', function($){
		return/** @lends ss.Form_EditForm_action_print */{
			onclick: function(e) {
				var printURL = $(this[0].form).attr('action').replace(/\?.*$/,'') 
					+ '/printable/' 
					+ $(':input[name=ID]',this[0].form).val();
				if(printURL.substr(0,7) != 'http://') printURL = $('base').attr('href') + printURL;

				window.open(printURL, 'printable');
		
				return false;
			}
		};
	});

	/**
	 * @class A "rollback" to a specific version needs user confirmation.
	 * @name ss.Form_EditForm_action_rollback
	 */
	$('#Form_EditForm .Actions #Form_EditForm_action_rollback').concrete('ss', function($){
		return/** @lends ss.Form_EditForm_action_rollback */{
			onclick: function(e) {
				// @todo i18n
				return confirm("Do you really want to copy the published content to the stage site?");
			}
		};
	});
}(jQuery));