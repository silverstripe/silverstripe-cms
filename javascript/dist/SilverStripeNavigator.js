(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.SilverStripeNavigator', ['../../../framework/javascript/dist/jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('../../../framework/javascript/dist/jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssSilverStripeNavigator = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	function windowName(suffix) {
		var base = document.getElementsByTagName('base')[0].href.replace('http://', '').replace(/\//g, '_').replace(/\./g, '_');
		return base + suffix;
	}

	(0, _jQuery2.default)(document).ready(function () {
		(0, _jQuery2.default)('#switchView a.newWindow').on('click', function (e) {
			var w = window.open(this.href, windowName(this.target));
			w.focus();
			return false;
		});
		(0, _jQuery2.default)('#SilverStripeNavigatorLink').on('click', function (e) {
			(0, _jQuery2.default)('#SilverStripeNavigatorLinkPopup').toggle();
			return false;
		});
		(0, _jQuery2.default)('#SilverStripeNavigatorLinkPopup a.close').on('click', function (e) {
			(0, _jQuery2.default)('#SilverStripeNavigatorLinkPopup').hide();
			return false;
		});
		(0, _jQuery2.default)('#SilverStripeNavigatorLinkPopup input').on('focus', function (e) {
			this.select();
		});
	});
});