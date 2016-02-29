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

  /**
   * Behaviour for the CMS Content Toolbar.
   * Applies to tools on top-level views i.e. '/admin/pages' and '/admin/assets' and
   * their corresponding tools in the SiteTree panel.
   * An example is 'bulk actions' on the Pages view.
   */
  _jQuery2.default.entwine('ss', function ($) {
    // Faux three column layout
    $('.cms-content-header-info').entwine({
      'from .cms-panel': {
        // Keep the header info's width synced with the TreeView panel's width.
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

        // Initialise the buttons
        $.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
          var $button = $(this);
          var toolId = $button.data('toolid');

          // We don't care about tools that don't have a related 'action'.
          if (toolId !== void 0) {
            // Set the tool to its closed state.
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

      /**
       * @func bindActionButtonEvents
       * @param {object} $button
       * @desc Add event handlers in the '.cmsContentToolbar' namespace.
       */
      bindActionButtonEvents: function bindActionButtonEvents($button) {
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
      unbindActionButtonEvents: function unbindActionButtonEvents($button) {
        $button.off('.cmsContentToolbar');
      },

      /**
       * @func showTool
       * @param {object} $button
       * @desc Show a tool in the tools row. Hides all other tools.
       */
      showHideTool: function showHideTool($button) {
        var isActive = $button.data('active');
        var toolId = $button.data('toolid');
        var $action = $('#' + toolId);

        // Hide all tools except the one passed as a param,
        // which gets handled separately.
        $.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
          var $currentButton = $(this);
          var $currentAction = $('#' + $currentButton.data('toolid'));

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