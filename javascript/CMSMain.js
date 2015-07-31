(function ($) {
	/**
	 * Behaviour for the CMS Content Toolbar.
	 * Applies to tools on top-level views i.e. '/admin/pages' and '/admin/assets' and
	 * their corresponding tools in the SiteTree panel.
	 * An example is 'bulk actions' on the Pages view.
	 */
	$.entwine('ss', function ($) {

		// Faux three column layout
		$('.cms-content-header-info').entwine({
			'from .cms-panel': {
				// Keep the header info's width synced with the TreeView panel's width.
				ontoggle: function (e) {
					var $treeViewPanel = this.closest('.cms-content').find(e.target);

					if ($treeViewPanel.length === 0) {
						return;
					}

					this.parent()[$treeViewPanel.hasClass('collapsed') ? 'addClass' : 'removeClass']('collapsed');
				}
			}
		});

		$('.cms-content-toolbar').entwine({

			onmatch: function () {
				var self = this;

				this._super();

				// Initialise the buttons
				$.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
					var $button = $(this),
						toolId = $button.data('toolid'),
						isActive = $button.hasClass('active');

					// We don't care about tools that don't have a related 'action'.
					if (toolId !== void 0) {
						// Set the tool to its closed state.
						$button.data('active', false).removeClass('active');
						$('#' + toolId).hide();

						self.bindActionButtonEvents($button);
					}
				});
			},

			onunmatch: function () {
				var self = this;

				this._super();

				$.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
					var $button = $(this);
					self.unbindActionButtonEvents($button);
				});
			},

			/**
			 * @func bindActionButtonEvents
			 * @param {object} $button
			 * @desc Add event handlers in the '.cmsContentToolbar' namespace.
			 */
			bindActionButtonEvents: function ($button) {
				var self = this;

				$button.on('click.cmsContentToolbar', function (e) {
					self.showHideTool($button);
				});
			},

			/**
			 * @func unbindActionButtonEvents
			 * @param {object} $button
			 * @desc Remove all event handlers in the '.cmsContentToolbar' namespace.
			 */
			unbindActionButtonEvents: function ($button) {
				$button.off('.cmsContentToolbar');
			},

			/**
			 * @func showTool
			 * @param {object} $button
			 * @desc Show a tool in the tools row. Hides all other tools.
			 */
			showHideTool: function ($button) {
				var isActive = $button.data('active'),
					toolId = $button.data('toolid'),
					$action = $('#' + toolId);

				// Hide all tools except the one passed as a param,
				// which gets handled separately.
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
}(jQuery));
