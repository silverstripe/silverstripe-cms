(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.CMSMain', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssCMSMain = mod.exports;
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
		$('.cms-content-header-info').entwine({
			'from .cms-panel': {
				ontoggle: function ontoggle(e) {
					var $treeViewPanel = this.closest('.cms-content').find(e.target);

					if ($treeViewPanel.length === 0) {
						return;
					}

					this.parent()[$treeViewPanel.hasClass('collapsed') ? 'addClass' : 'removeClass']('collapsed');
				}
			}
		});

		$('.cms-content-toolbar').entwine({

			onmatch: function onmatch() {
				var self = this;

				this._super();

				$.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
					var $button = $(this),
					    toolId = $button.data('toolid'),
					    isActive = $button.hasClass('active');

					if (toolId !== void 0) {
						$button.data('active', false).removeClass('active');
						$('#' + toolId).hide();

						self.bindActionButtonEvents($button);
					}
				});
			},

			onunmatch: function onunmatch() {
				var self = this;

				this._super();

				$.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
					var $button = $(this);
					self.unbindActionButtonEvents($button);
				});
			},

			bindActionButtonEvents: function bindActionButtonEvents($button) {
				var self = this;

				$button.on('click.cmsContentToolbar', function (e) {
					self.showHideTool($button);
				});
			},

			unbindActionButtonEvents: function unbindActionButtonEvents($button) {
				$button.off('.cmsContentToolbar');
			},

			showHideTool: function showHideTool($button) {
				var isActive = $button.data('active'),
				    toolId = $button.data('toolid'),
				    $action = $('#' + toolId);

				$.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
					var $currentButton = $(this),
					    $currentAction = $('#' + $currentButton.data('toolid'));

					if ($currentButton.data('toolid') !== toolId) {
						$currentAction.hide();
						$currentButton.data('active', false);
					}
				});

				$button[isActive ? 'removeClass' : 'addClass']('active');
				$action[isActive ? 'hide' : 'show']();
				$button.data('active', !isActive);
			}
		});
	});
});