(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.CMSMain.EditForm', ['jQuery', 'i18n'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'), require('i18n'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery, global.i18n);
		global.ssCMSMainEditForm = mod.exports;
	}
})(this, function (_jQuery, _i18n) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	var _i18n2 = _interopRequireDefault(_i18n);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	/**
  * File: CMSMain.EditForm.js
  */


	_jQuery2.default.entwine('ss', function ($) {
		/**
   * Class: .cms-edit-form :input[name=ClassName]
   * Alert the user on change of page-type. This might have implications
   * on the available form fields etc.
   */
		$('.cms-edit-form :input[name=ClassName]').entwine({
			// Function: onchange
			onchange: function onchange() {
				alert(_i18n2.default._t('CMSMAIN.ALERTCLASSNAME'));
			}
		});

		/**
   * Class: .cms-edit-form input[name=Title]
   *
   * Input validation on the Title field
   */
		$('.cms-edit-form input[name=Title]').entwine({
			// Constructor: onmatch
			onmatch: function onmatch() {
				var self = this;

				self.data('OrigVal', self.val());

				var form = self.closest('form');
				var urlSegmentInput = $('input:text[name=URLSegment]', form);
				var liveLinkInput = $('input[name=LiveLink]', form);

				if (urlSegmentInput.length > 0) {
					self._addActions();
					this.bind('change', function (e) {
						var origTitle = self.data('OrigVal');
						var title = self.val();
						self.data('OrigVal', title);

						// Criteria for defining a "new" page
						if (urlSegmentInput.val().indexOf(urlSegmentInput.data('defaultUrl')) === 0 && liveLinkInput.val() == '') {
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
			onunmatch: function onunmatch() {
				this._super();
			},

			/**
    * Function: updateRelatedFields
    *
    * Update the related fields if appropriate
    * (String) title The new title
    * (Stirng) origTitle The original title
    */
			updateRelatedFields: function updateRelatedFields(title, origTitle) {
				// Update these fields only if their value was originally the same as the title
				this.parents('form').find('input[name=MetaTitle], input[name=MenuTitle]').each(function () {
					var $this = $(this);
					if ($this.val() == origTitle) {
						$this.val(title);
						// Onchange bubbling didn't work in IE8, so .trigger('change') couldn't be used
						if ($this.updatedRelatedFields) $this.updatedRelatedFields();
					}
				});
			},

			/**
    * Function: updateURLSegment
    *
    * Update the URLSegment
    * (String) title
    */
			updateURLSegment: function updateURLSegment(title) {
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
			updateBreadcrumbLabel: function updateBreadcrumbLabel(title) {
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
			_addActions: function _addActions() {
				var self = this;
				var updateURLFromTitle;

				// update button
				updateURLFromTitle = $('<button />', {
					'class': 'update ss-ui-button-small',
					'text': _i18n2.default._t('URLSEGMENT.UpdateURL'),
					'type': 'button',
					'click': function click(e) {
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
			onmatch: function onmatch() {
				var self = this;
				this.find(':input[name=ParentType]').bind('click', function (e) {
					self._toggleSelection(e);
				});
				this.find('.TreeDropdownField').bind('change', function (e) {
					self._changeParentId(e);
				});

				this._changeParentId();
				this._toggleSelection();

				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			},

			/**
    * Function: _toggleSelection
    *
    * Parameters:
    *  (Event) e
    */
			_toggleSelection: function _toggleSelection(e) {
				var selected = this.find(':input[name=ParentType]:checked').val();
				// reset parent id if 'root' radiobutton is selected
				if (selected == 'root') this.find(':input[name=ParentID]').val(0);
				// otherwise use the old value
				else this.find(':input[name=ParentID]').val(this.find('#Form_EditForm_ParentType_subpage').data('parentIdValue'));
				// toggle tree dropdown based on selection
				this.find('#Form_EditForm_ParentID_Holder').toggle(selected != 'root');
			},

			/**
    * Function: _changeParentId
    *
    * Parameters:
    *  (Event) e
    */
			_changeParentId: function _changeParentId(e) {
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
		$('.cms-edit-form #CanViewType, .cms-edit-form #CanEditType, .cms-edit-form #CanCreateTopLevelType').entwine({
			// Constructor: onmatch
			onmatch: function onmatch() {
				// TODO Decouple
				var dropdown;
				if (this.attr('id') == 'CanViewType') dropdown = $('#Form_EditForm_ViewerGroups_Holder');else if (this.attr('id') == 'CanEditType') dropdown = $('#Form_EditForm_EditorGroups_Holder');else if (this.attr('id') == 'CanCreateTopLevelType') dropdown = $('#Form_EditForm_CreateTopLevelGroups_Holder');

				this.find('.optionset :input').bind('change', function (e) {
					var wrapper = $(this).closest('.middleColumn').parent('div');
					if (e.target.value == 'OnlyTheseUsers') {
						wrapper.addClass('remove-splitter');
						dropdown['show']();
					} else {
						wrapper.removeClass('remove-splitter');
						dropdown['hide']();
					}
				});

				// initial state
				var currentVal = this.find('input[name=' + this.attr('id') + ']:checked').val();
				dropdown[currentVal == 'OnlyTheseUsers' ? 'show' : 'hide']();

				this._super();
			},
			onunmatch: function onunmatch() {
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
			onclick: function onclick(e) {
				var printURL = $(this[0].form).attr('action').replace(/\?.*$/, '') + '/printable/' + $(':input[name=ID]', this[0].form).val();
				if (printURL.substr(0, 7) != 'http://') printURL = $('base').attr('href') + printURL;

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
			onclick: function onclick(e) {
				var form = this.parents('form:first'),
				    version = form.find(':input[name=Version]').val(),
				    message = '';
				if (version) {
					message = _i18n2.default.sprintf(_i18n2.default._t('CMSMain.RollbackToVersion'), version);
				} else {
					message = _i18n2.default._t('CMSMain.ConfirmRestoreFromLive');
				}
				if (confirm(message)) {
					return this._super(e);
				} else {
					return false;
				}
			}
		});

		/**
   * Class: .cms-edit-form .Actions #Form_EditForm_action_archive
   *
   * Informing the user about the archive action while requiring confirmation
   */
		$('.cms-edit-form .Actions #Form_EditForm_action_archive').entwine({

			/**
    * Function: onclick
    *
    * Parameters:
    *  (Event) e
    */
			onclick: function onclick(e) {
				var form = this.parents('form:first'),
				    version = form.find(':input[name=Version]').val(),
				    message = '';
				message = _i18n2.default.sprintf(_i18n2.default._t('CMSMain.Archive'), version);
				if (confirm(message)) {
					return this._super(e);
				} else {
					return false;
				}
			}
		});

		/**
   * Class: .cms-edit-form .Actions #Form_EditForm_action_restore
   *
   * Informing the user about the archive action while requiring confirmation
   */
		$('.cms-edit-form .Actions #Form_EditForm_action_restore').entwine({

			/**
    * Function: onclick
    *
    * Parameters:
    *  (Event) e
    */
			onclick: function onclick(e) {
				var form = this.parents('form:first'),
				    version = form.find(':input[name=Version]').val(),
				    message = '',
				    toRoot = this.data('toRoot');
				message = _i18n2.default.sprintf(_i18n2.default._t(toRoot ? 'CMSMain.RestoreToRoot' : 'CMSMain.Restore'), version);
				if (confirm(message)) {
					return this._super(e);
				} else {
					return false;
				}
			}
		});

		/**
   * Class: .cms-edit-form .Actions #Form_EditForm_action_delete
   *
   * Informing the user about the delete from draft action while requiring confirmation
   */
		$('.cms-edit-form .Actions #Form_EditForm_action_delete').entwine({

			/**
    * Function: onclick
    *
    * Parameters:
    *  (Event) e
    */
			onclick: function onclick(e) {
				var form = this.parents('form:first'),
				    version = form.find(':input[name=Version]').val(),
				    message = '';
				message = _i18n2.default.sprintf(_i18n2.default._t('CMSMain.DeleteFromDraft'), version);
				if (confirm(message)) {
					return this._super(e);
				} else {
					return false;
				}
			}
		});

		/**
   * Class: .cms-edit-form .Actions #Form_EditForm_action_unpublish
   * Informing the user about the unpublish action while requiring confirmation
   */
		$('.cms-edit-form .Actions #Form_EditForm_action_unpublish').entwine({

			/**
    * Function: onclick
    *
    * Parameters:
    *  (Event) e
    */
			onclick: function onclick(e) {
				var form = this.parents('form:first'),
				    version = form.find(':input[name=Version]').val(),
				    message = '';
				message = _i18n2.default.sprintf(_i18n2.default._t('CMSMain.Unpublish'), version);
				if (confirm(message)) {
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
			onmatch: function onmatch(e) {
				this.find('button[name=action_save]').button('option', 'showingAlternate', true);
				this.find('button[name=action_publish]').button('option', 'showingAlternate', true);
				this._super(e);
			},
			onunmatch: function onunmatch(e) {
				var saveButton = this.find('button[name=action_save]');
				if (saveButton.data('button')) saveButton.button('option', 'showingAlternate', false);
				var publishButton = this.find('button[name=action_publish]');
				if (publishButton.data('button')) publishButton.button('option', 'showingAlternate', false);
				this._super(e);
			}
		});

		$('.cms-edit-form .Actions button[name=action_publish]').entwine({
			/**
    * Bind to ssui.button event to trigger stylistic changes.
    */
			onbuttonafterrefreshalternate: function onbuttonafterrefreshalternate() {
				if (this.button('option', 'showingAlternate')) {
					this.addClass('ss-ui-action-constructive');
				} else {
					this.removeClass('ss-ui-action-constructive');
				}
			}
		});

		$('.cms-edit-form .Actions button[name=action_save]').entwine({
			/**
    * Bind to ssui.button event to trigger stylistic changes.
    */
			onbuttonafterrefreshalternate: function onbuttonafterrefreshalternate() {
				if (this.button('option', 'showingAlternate')) {
					this.addClass('ss-ui-action-constructive');
				} else {
					this.removeClass('ss-ui-action-constructive');
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
			onmatch: function onmatch() {
				this.redraw();
				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			redraw: function redraw() {
				var treeField = $('.cms-edit-form.CMSPageSettingsController #Form_EditForm_ParentID_Holder');
				if ($(this).attr('id') == 'Form_EditForm_ParentType_root') treeField.slideUp();else treeField.slideDown();
			},
			onclick: function onclick() {
				this.redraw();
			}
		});

		//trigger an initial change event to do the initial hiding of the element, if necessary
		if ($('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').attr('id') == 'Form_EditForm_ParentType_root') {
			$('.cms-edit-form.CMSPageSettingsController #Form_EditForm_ParentID_Holder').hide(); //quick hide on first run
		}
	});
});