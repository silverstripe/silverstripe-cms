(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.AssetAdmin', ['jQuery', 'i18n'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'), require('i18n'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery, global.i18n);
		global.ssAssetAdmin = mod.exports;
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
		$('.AssetAdmin.cms-edit-form .ss-gridfield-item').entwine({
			onclick: function onclick(e) {
				if ($(e.target).closest('.action').length) {
					this._super(e);
					return;
				}

				var grid = this.closest('.ss-gridfield');
				if (this.data('class') == 'Folder') {
					var url = grid.data('urlFolderTemplate').replace('%s', this.data('id'));
					$('.cms-container').loadPanel(url);
					return false;
				}

				this._super(e);
			}
		});

		$('.AssetAdmin.cms-edit-form .ss-gridfield .col-buttons .action.gridfield-button-delete, .AssetAdmin.cms-edit-form .Actions button.action.action-delete').entwine({
			onclick: function onclick(e) {
				var msg;
				if (this.closest('.ss-gridfield-item').data('class') == 'Folder') {
					msg = _i18n2.default._t('AssetAdmin.ConfirmDelete');
				} else {
					msg = _i18n2.default._t('TABLEFIELD.DELETECONFIRMMESSAGE');
				}
				if (!confirm(msg)) return false;

				this.getGridField().reload({ data: [{ name: this.attr('name'), value: this.val() }] });
				e.preventDefault();
				return false;
			}
		});

		$('.AssetAdmin.cms-edit-form :submit[name=action_delete]').entwine({
			onclick: function onclick(e) {
				if (!confirm(_i18n2.default._t('AssetAdmin.ConfirmDelete'))) return false;else this._super(e);
			}
		});

		$('.AssetAdmin .cms-add-folder-link').entwine({
			onclick: function onclick(e) {
				var name = prompt(_i18n2.default._t('Folder.Name'));
				if (!name) return false;

				this.closest('.cms-container').loadPanel(this.data('url') + '&Name=' + name);
				return false;
			}
		});

		$('#Form_SyncForm').entwine({
			onsubmit: function onsubmit(e) {
				var button = jQuery(this).find(':submit:first');
				button.addClass('loading');
				$.ajax({
					url: jQuery(this).attr('action'),
					data: this.serializeArray(),
					success: function success() {
						button.removeClass('loading');

						var currNode = $('.cms-tree')[0].firstSelected();
						if (currNode) {
							var url = $(currNode).find('a').attr('href');
							$('.cms-content').loadPanel(url);
						}
						$('.cms-tree')[0].setCustomURL('admin/assets/getsubtree');
						$('.cms-tree')[0].reload({ onSuccess: function onSuccess() {} });
					}
				});

				return false;
			}
		});

		$('.AssetAdmin.cms-edit-form .ss-uploadfield-item-progress').entwine({
			onunmatch: function onunmatch() {
				$('.AssetAdmin.cms-edit-form .ss-gridfield').reload();
			}
		});

		$('.AssetAdmin .grid-levelup').entwine({
			onmatch: function onmatch() {
				this.closest('.ui-tabs-panel').find('.cms-actions-row').prepend(this);
			}
		});
	});
});