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

	_jQuery2.default.entwine('ss', function ($) {
		$('.cms-edit-form :input[name=ClassName]').entwine({
			onchange: function onchange() {
				alert(_i18n2.default._t('CMSMAIN.ALERTCLASSNAME'));
			}
		});

		$('.cms-edit-form input[name=Title]').entwine({
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

			updateRelatedFields: function updateRelatedFields(title, origTitle) {
				this.parents('form').find('input[name=MetaTitle], input[name=MenuTitle]').each(function () {
					var $this = $(this);
					if ($this.val() == origTitle) {
						$this.val(title);

						if ($this.updatedRelatedFields) $this.updatedRelatedFields();
					}
				});
			},

			updateURLSegment: function updateURLSegment(title) {
				var urlSegmentInput = $('input:text[name=URLSegment]', this.closest('form'));
				var urlSegmentField = urlSegmentInput.closest('.field.urlsegment');
				var updateURLFromTitle = $('.update', this.parent());
				urlSegmentField.update(title);
				if (updateURLFromTitle.is(':visible')) {
					updateURLFromTitle.hide();
				}
			},

			updateBreadcrumbLabel: function updateBreadcrumbLabel(title) {
				var pageID = $('.cms-edit-form input[name=ID]').val();
				var panelCrumb = $('span.cms-panel-link.crumb');
				if (title && title != "") {
					panelCrumb.text(title);
				}
			},

			_addActions: function _addActions() {
				var self = this;
				var updateURLFromTitle;

				updateURLFromTitle = $('<button />', {
					'class': 'update ss-ui-button-small',
					'text': _i18n2.default._t('URLSEGMENT.UpdateURL'),
					'type': 'button',
					'click': function click(e) {
						e.preventDefault();
						self.updateURLSegment(self.val());
					}
				});

				updateURLFromTitle.insertAfter(self);
				updateURLFromTitle.hide();
			}
		});

		$('.cms-edit-form .parentTypeSelector').entwine({
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

			_toggleSelection: function _toggleSelection(e) {
				var selected = this.find(':input[name=ParentType]:checked').val();

				if (selected == 'root') this.find(':input[name=ParentID]').val(0);else this.find(':input[name=ParentID]').val(this.find('#Form_EditForm_ParentType_subpage').data('parentIdValue'));

				this.find('#Form_EditForm_ParentID_Holder').toggle(selected != 'root');
			},

			_changeParentId: function _changeParentId(e) {
				var value = this.find(':input[name=ParentID]').val();

				this.find('#Form_EditForm_ParentType_subpage').data('parentIdValue', value);
			}
		});

		$('.cms-edit-form #CanViewType, .cms-edit-form #CanEditType, .cms-edit-form #CanCreateTopLevelType').entwine({
			onmatch: function onmatch() {
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

				var currentVal = this.find('input[name=' + this.attr('id') + ']:checked').val();
				dropdown[currentVal == 'OnlyTheseUsers' ? 'show' : 'hide']();

				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			}
		});

		$('.cms-edit-form .Actions #Form_EditForm_action_print').entwine({
			onclick: function onclick(e) {
				var printURL = $(this[0].form).attr('action').replace(/\?.*$/, '') + '/printable/' + $(':input[name=ID]', this[0].form).val();
				if (printURL.substr(0, 7) != 'http://') printURL = $('base').attr('href') + printURL;

				window.open(printURL, 'printable');

				return false;
			}
		});

		$('.cms-edit-form .Actions #Form_EditForm_action_rollback').entwine({
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

		$('.cms-edit-form .Actions #Form_EditForm_action_archive').entwine({
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

		$('.cms-edit-form .Actions #Form_EditForm_action_restore').entwine({
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

		$('.cms-edit-form .Actions #Form_EditForm_action_delete').entwine({
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

		$('.cms-edit-form .Actions #Form_EditForm_action_unpublish').entwine({
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
			onbuttonafterrefreshalternate: function onbuttonafterrefreshalternate() {
				if (this.button('option', 'showingAlternate')) {
					this.addClass('ss-ui-action-constructive');
				} else {
					this.removeClass('ss-ui-action-constructive');
				}
			}
		});

		$('.cms-edit-form .Actions button[name=action_save]').entwine({
			onbuttonafterrefreshalternate: function onbuttonafterrefreshalternate() {
				if (this.button('option', 'showingAlternate')) {
					this.addClass('ss-ui-action-constructive');
				} else {
					this.removeClass('ss-ui-action-constructive');
				}
			}
		});

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

		if ($('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').attr('id') == 'Form_EditForm_ParentType_root') {
			$('.cms-edit-form.CMSPageSettingsController #Form_EditForm_ParentID_Holder').hide();
		}
	});
});