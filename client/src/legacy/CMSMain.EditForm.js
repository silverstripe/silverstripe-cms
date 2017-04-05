
/**
 * File: CMSMain.EditForm.js
 */
import $ from 'jQuery';
import i18n from 'i18n';

$.entwine('ss', function($){
	/**
	 * Class: .cms-edit-form :input[name=ClassName]
	 * Alert the user on change of page-type. This might have implications
	 * on the available form fields etc.
	 */
	$('.cms-edit-form :input[name=ClassName]').entwine({
		// Function: onchange
		onchange: function() {
			alert(i18n._t('CMSMAIN.ALERTCLASSNAME'));
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

			self.data('OrigVal', self.val());

			var form = self.closest('form');
			var urlSegmentInput = $('input:text[name=URLSegment]', form);
			var liveLinkInput = $('input[name=LiveLink]', form);

			if (urlSegmentInput.length > 0) {
				self._addActions();
				this.bind('change', function(e) {
					var origTitle = self.data('OrigVal');
					var title = self.val();
					self.data('OrigVal', title);

					// Criteria for defining a "new" page
					if (
						urlSegmentInput.val().indexOf(urlSegmentInput.data('defaultUrl')) === 0
						&& liveLinkInput.val() == ''
					) {
						self.updateURLSegment(title);
					} else {
						$('.update', self.parent()).show();
					}

					self.updateRelatedFields(title, origTitle);
					self.updateBreadcrumbLabel(title);
				});
			}

			this._super();
		},
		onunmatch: function() {
			this._super();
		},

		/**
		 * Function: updateRelatedFields
		 *
		 * Update the related fields if appropriate
		 * (String) title The new title
		 * (Stirng) origTitle The original title
		 */
		updateRelatedFields: function(title, origTitle) {
			// Update these fields only if their value was originally the same as the title
			this.parents('form').find('input[name=MetaTitle], input[name=MenuTitle]').each(function() {
				var $this = $(this);
				if($this.val() == origTitle) {
					$this.val(title);
					// Onchange bubbling didn't work in IE8, so .trigger('change') couldn't be used
					if($this.updatedRelatedFields) $this.updatedRelatedFields();
				}
			});
		},

		/**
		 * Function: updateURLSegment
		 *
		 * Update the URLSegment
		 * (String) title
		 */
		updateURLSegment: function(title) {
			var urlSegmentInput = $('input:text[name=URLSegment]', this.closest('form'));
			var urlSegmentField = urlSegmentInput.closest('.field.urlsegment');
			var updateURLFromTitle = $('.update', this.parent());
			urlSegmentField.update(title);
			if (updateURLFromTitle.is(':visible')) {
				updateURLFromTitle.hide();
			}
		},

		/**
		 * Function: updateBreadcrumbLabel
		 *
		 * Update the breadcrumb
		 * (String) title
		 */
		updateBreadcrumbLabel: function(title) {
			var pageID = $('.cms-edit-form input[name=ID]').val();
			var panelCrumb = $('span.cms-panel-link.crumb');
			if (title && title != "") {
				panelCrumb.text(title);
			}
		},

		/**
		 * Function: _addActions
		 *
		 * Utility to add update from title action
		 *
		 */
		_addActions: function() {
			var self = this;
			var	updateURLFromTitle;

			// update button
			updateURLFromTitle = $('<button />', {
				'class': 'update ss-ui-button-small',
				'text': i18n._t('URLSEGMENT.UpdateURL'),
				'type': 'button',
				'click': function(e) {
					e.preventDefault();
					self.updateURLSegment(self.val());
				}
			});

			// insert elements
			updateURLFromTitle.insertAfter(self);
			updateURLFromTitle.hide();
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
			this.find('.TreeDropdownField').bind('change', function(e) {self._changeParentId(e);});

			this._changeParentId();
			this._toggleSelection();

			this._super();
		},
		onunmatch: function() {
			this._super();
		},

		/**
		 * Function: _toggleSelection
		 *
		 * Parameters:
		 *  (Event) e
		 */
		_toggleSelection: function(e) {
			var selected = this.find(':input[name=ParentType]:checked').val(),
				holder = this.find('#Form_EditForm_ParentID_Holder');
			// reset parent id if 'root' radiobutton is selected
			if(selected == 'root') this.find(':input[name=ParentID]').val(0);
			// otherwise use the old value
			else this.find(':input[name=ParentID]').val(this.find('#Form_EditForm_ParentType_subpage').data('parentIdValue'));
			// toggle tree dropdown based on selection
			if(selected != 'root') {
				holder.slideDown(400, function() {
					$(this).css('overflow', 'visible');
				});
			} else {
				holder.slideUp();
			}
		},

		/**
		 * Function: _changeParentId
		 *
		 * Parameters:
		 *  (Event) e
		 */
		_changeParentId: function(e) {
			var value = this.find(':input[name=ParentID]').val();
			// set a data attribute so we know what to use in _toggleSelection
			this.find('#Form_EditForm_ParentType_subpage').data('parentIdValue', value);
		}
	});

	/**
	 * Class: .cms-edit-form #CanViewType, .cms-edit-form #CanEditType
	 *
	 * Toggle display of group dropdown in "access" tab,
	 * based on selection of radiobuttons.
	 */
  $('.cms-edit-form [name="CanViewType"], ' +
    '.cms-edit-form [name="CanEditType"], ' +
    '.cms-edit-form #CanCreateTopLevelType').entwine({
    onmatch: function () {
      if (this.val() === 'OnlyTheseUsers') {
        if (this.is(':checked')) {
          this.showList(true);
        } else {
          this.hideList(true);
        }
      }
    },
    onchange: function (e) {
      if (e.target.value === 'OnlyTheseUsers') {
        this.showList();
      } else {
        this.hideList();
      }
    },
    showList: function (instant) {
      let holder = this.closest('.field');

      holder.addClass('field--merge-below');
      holder.next().filter('.listbox')[instant ? 'show' : 'slideDown']();
    },
    hideList: function (instant) {
      let holder = this.closest('.field');

      holder.next().filter('.listbox')[instant ? 'hide' : 'slideUp'](() => {
        holder.removeClass('field--merge-below');
      });
    }
  });

	/**
	 * Class: .cms-edit-form .btn-toolbar #Form_EditForm_action_print
	 *
	 * Open a printable representation of the form in a new window.
	 * Used for readonly older versions of a specific page.
	 */
	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_print').entwine({
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
	 * Class: .cms-edit-form .btn-toolbar #Form_EditForm_action_rollback
	 *
	 * A "rollback" to a specific version needs user confirmation.
	 */
	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_rollback').entwine({

		/**
		 * Function: onclick
		 *
		 * Parameters:
		 *  (Event) e
		 */
		onclick: function(e) {
			var form = this.parents('form:first'), version = form.find(':input[name=Version]').val(), message = '';
			if(version) {
				message = i18n.sprintf(
					i18n._t('CMSMain.RollbackToVersion'),
					version
				);
			} else {
				message = i18n._t('CMSMain.ConfirmRestoreFromLive');
			}
			if(confirm(message)) {
				return this._super(e);
			} else {
				return false;
			}
		}
	});

	/**
	 * Class: .cms-edit-form .btn-toolbar #Form_EditForm_action_archive
	 *
	 * Informing the user about the archive action while requiring confirmation
	 */
	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_archive').entwine({

		/**
		 * Function: onclick
		 *
		 * Parameters:
		 *  (Event) e
		 */
		onclick: function(e) {
			var form = this.parents('form:first'), message = '';
			message = form.find('input[name=ArchiveWarningMessage]')
				.val()
				.replace(/\\n/g, '\n');

			if(confirm(message)) {
				return this._super(e);
			} else {
				return false;
			}
		}
	});

	/**
	 * Class: .cms-edit-form .btn-toolbar #Form_EditForm_action_restore
	 *
	 * Informing the user about the archive action while requiring confirmation
	 */
	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_restore').entwine({

		/**
		 * Function: onclick
		 *
		 * Parameters:
		 *  (Event) e
		 */
		onclick: function(e) {
			var form = this.parents('form:first'),
				version = form.find(':input[name=Version]').val(),
				message = '',
				toRoot = this.data('toRoot');
			message = i18n.sprintf(
				i18n._t(toRoot ? 'CMSMain.RestoreToRoot' : 'CMSMain.Restore'),
				version
			);
			if(confirm(message)) {
				return this._super(e);
			} else {
				return false;
			}
		}
	});

	/**
	 * Class: .cms-edit-form .btn-toolbar #Form_EditForm_action_unpublish
	 * Informing the user about the unpublish action while requiring confirmation
	 */
	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_unpublish').entwine({

		/**
		 * Function: onclick
		 *
		 * Parameters:
		 *  (Event) e
		 */
		onclick: function(e) {
			var form = this.parents('form:first'), version = form.find(':input[name=Version]').val(), message = '';
			message = i18n.sprintf(
				i18n._t('CMSMain.Unpublish'),
				version
			);
			if(confirm(message)) {
				return this._super(e);
			} else {
				return false;
			}
		}
	});

	/**
	 * Enable save buttons upon detecting changes to content.
	 * "changed" class is added by jQuery.changetracker.
	 */
  $('.cms-edit-form.changed').entwine({
    onmatch: function(e) {
      var save = this.find('button[name=action_save]');

      if(save.attr('data-text-alternate')) {
        save.attr('data-text-standard', save.text());
        save.text(save.attr('data-text-alternate'));
      }

      if(save.attr('data-btn-alternate')) {
        save.attr('data-btn-standard', save.attr('class'));
        save.attr('class', save.attr('data-btn-alternate'));
      }


      save
        .removeClass('btn-secondary-outline')
        .addClass('btn-primary');

			var publish = this.find('button[name=action_publish]')

      if(publish.attr('data-text-alternate')) {
        publish.attr('data-text-standard', publish.attr('data-text-alternate'));
        publish.text(publish.attr('data-text-alternate'));
      }

      if(publish.attr('data-btn-alternate')) {
        publish.attr('data-btn-standard', publish.attr('class'));
        publish.attr('class', publish.attr('data-btn-alternate'));
      }

      publish
        .removeClass('btn-secondary-outline')
        .addClass('btn-primary');


			this._super(e);
		},
		onunmatch: function(e) {
      var save = this.find('button[name=action_save]')

      if(save.attr('data-text-standard')) {
        save.text(save.attr('data-text-standard'));
      }

      if(save.attr('data-btn-standard')) {
        save.attr('class', save.attr('data-btn-standard'));
      }

      var publish = this.find('button[name=action_publish]');

      if(publish.attr('data-text-standard')) {
        publish.text(publish.attr('data-text-standard'));
      }

      if(publish.attr('data-btn-standard')) {
        publish.attr('class', publish.attr('data-btn-standard'));
      }

			this._super(e);
		}
	});

	$('.cms-edit-form .btn-toolbar button[name=action_publish]').entwine({
		/**
		 * Bind to ssui.button event to trigger stylistic changes.
		 */
		onbuttonafterrefreshalternate: function() {
			if (this.data('showingAlternate')) {
				this.addClass('btn-primary');
        this.removeClass('btn-secondary');
			}
			else {
				this.removeClass('btn-primary');
        this.addClass('btn-secondary');
			}
		}
	});

	$('.cms-edit-form .btn-toolbar button[name=action_save]').entwine({
		/**
		 * Bind to ssui.button event to trigger stylistic changes.
		 */
		onbuttonafterrefreshalternate: function() {
			if (this.data('showingAlternate')) {
				this.addClass('btn-primary');
        this.removeClass('btn-secondary');
			}
			else {
				this.removeClass('btn-primary');
        this.addClass('btn-secondary');
			}
		}
	});

	/**
	 * Class: .cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked
	 *
	 * Showing the "Page location" "Parent page" chooser only when the "Sub-page underneath a parent page"
	 * radio button is selected
	 */
	$('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').entwine({
		onmatch: function() {
			this.redraw();
			this._super();
		},
		onunmatch: function() {
			this._super();
		},
		redraw: function() {
			var treeField = $('.cms-edit-form.CMSPageSettingsController #Form_EditForm_ParentID_Holder');
			if ($(this).attr('id') == 'Form_EditForm_ParentType_root') treeField.slideUp();
			else treeField.slideDown();
		},
		onclick: function() {
			this.redraw();
		}
	});

	//trigger an initial change event to do the initial hiding of the element, if necessary
	if ($('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').attr('id') == 'Form_EditForm_ParentType_root') {
		$('.cms-edit-form.CMSPageSettingsController #Form_EditForm_ParentID_Holder').hide(); //quick hide on first run
	}
});
