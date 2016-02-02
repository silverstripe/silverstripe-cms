(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.RedirectorPage', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssRedirectorPage = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	_jQuery2.default.entwine('ss', function ($) {
		$('#Form_EditForm_RedirectionType input').entwine({
			onmatch: function onmatch() {
				var self = $(this);
				if (self.attr('checked')) this.toggle();

				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			onclick: function onclick() {
				this.toggle();
			},
			toggle: function toggle() {
				if ($(this).attr('value') == 'Internal') {
					$('#Form_EditForm_ExternalURL_Holder').hide();
					$('#Form_EditForm_LinkToID_Holder').show();
				} else {
					$('#Form_EditForm_ExternalURL_Holder').show();
					$('#Form_EditForm_LinkToID_Holder').hide();
				}
			}
		});
	});
});