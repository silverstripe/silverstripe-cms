(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.CMSPageHistoryController', ['jQuery', 'i18n'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'), require('i18n'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery, global.i18n);
		global.ssCMSPageHistoryController = mod.exports;
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
		$('#Form_VersionsForm').entwine({
			onmatch: function onmatch() {
				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			},

			onsubmit: function onsubmit(e, d) {
				e.preventDefault();

				var id,
				    self = this;

				id = this.find(':input[name=ID]').val();

				if (!id) return false;

				var button, url, selected, to, from, compare, data;

				compare = this.find(":input[name=CompareMode]").is(":checked");
				selected = this.find("table input[type=checkbox]").filter(":checked");

				if (compare) {
					if (selected.length != 2) return false;

					to = selected.eq(0).val();
					from = selected.eq(1).val();
					button = this.find(':submit[name=action_doCompare]');
					url = _i18n2.default.sprintf(this.data('linkTmplCompare'), id, from, to);
				} else {
					to = selected.eq(0).val();
					button = this.find(':submit[name=action_doShowVersion]');
					url = _i18n2.default.sprintf(this.data('linkTmplShow'), id, to);
				}

				$('.cms-container').loadPanel(url, '', { pjax: 'CurrentForm' });
			}
		});

		$('#Form_VersionsForm input[name=ShowUnpublished]').entwine({
			onmatch: function onmatch() {
				this.toggle();
				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			},

			onchange: function onchange() {
				this.toggle();
			},
			toggle: function toggle() {
				var self = $(this);
				var form = self.parents('form');

				if (self.attr('checked')) {
					form.find('tr[data-published=false]').show();
				} else {
					form.find("tr[data-published=false]").hide()._unselect();
				}
			}
		});

		$("#Form_VersionsForm tbody tr").entwine({
			onclick: function onclick(e) {
				var compare, selected;

				compare = this.parents("form").find(':input[name=CompareMode]').attr("checked");
				selected = this.siblings(".active");

				if (compare && this.hasClass('active')) {
					this._unselect();

					return;
				} else if (compare) {
					if (selected.length > 1) {
						return alert(_i18n2.default._t('ONLYSELECTTWO', 'You can only compare two versions at this time.'));
					}

					this._select();

					if (selected.length == 1) {
						this.parents('form').submit();
					}

					return;
				} else {
					this._select();
					selected._unselect();

					this.parents("form").submit();
				}
			},

			_unselect: function _unselect() {
				this.removeClass('active');
				this.find(":input[type=checkbox]").attr("checked", false);
			},

			_select: function _select() {
				this.addClass('active');
				this.find(":input[type=checkbox]").attr("checked", true);
			}

		});
	});
});