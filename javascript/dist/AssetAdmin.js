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

  /* global jQuery */

  /**
   * File: AssetAdmin.js
   */

  _jQuery2.default.entwine('ss', function ($) {
    /**
     * Delete selected folders through "batch actions" tab.
     */
    /* assets don't currently have batch actions; disabling for now
    $(document).ready(function() {
      $('#Form_BatchActionsForm').entwine('.ss.tree').register(
        // TODO Hardcoding of base URL
        'admin/assets/batchactions/delete',
        function(ids) {
          var confirmed = confirm(
            i18n.sprintf(
              i18n._t('AssetAdmin.BATCHACTIONSDELETECONFIRM'),
              ids.length
            )
          )
          return (confirmed) ? ids : false
        }
      )
    })
    */

    /**
     * Load folder detail view via controller methods
     * rather than built-in GridField view (which is only geared towards showing files).
     */
    $('.AssetAdmin.cms-edit-form .ss-gridfield-item').entwine({
      onclick: function onclick(e) {
        // Let actions do their own thing
        if ($(e.target).closest('.action').length) {
          this._super(e);
          return;
        }

        var grid = this.closest('.ss-gridfield');
        if (this.data('class') === 'Folder') {
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
        if (this.closest('.ss-gridfield-item').data('class') === 'Folder') {
          msg = _i18n2.default._t('AssetAdmin.ConfirmDelete');
        } else {
          msg = _i18n2.default._t('TABLEFIELD.DELETECONFIRMMESSAGE');
        }
        if (!window.confirm(msg)) return false;

        this.getGridField().reload({ data: [{ name: this.attr('name'), value: this.val() }] });
        e.preventDefault();
        return false;
      }
    });

    $('.AssetAdmin.cms-edit-form :submit[name=action_delete]').entwine({
      onclick: function onclick(e) {
        if (!window.confirm(_i18n2.default._t('AssetAdmin.ConfirmDelete'))) return false;else this._super(e);
      }
    });

    /**
     * Prompt for a new foldername, rather than using dedicated form.
     * Better usability, but less flexibility in terms of inputs and validation.
     * Mainly necessary because AssetAdmin->AddForm() returns don't play nicely
     * with the nested AssetAdmin->EditForm() DOM structures.
     */
    $('.AssetAdmin .cms-add-folder-link').entwine({
      onclick: function onclick(e) {
        var name = window.prompt(_i18n2.default._t('Folder.Name'));
        if (!name) return false;

        this.closest('.cms-container').loadPanel(this.data('url') + '&Name=' + name);
        return false;
      }
    });

    /**
     * Class: #Form_SyncForm
     */
    $('#Form_SyncForm').entwine({
      /**
       * Function: onsubmit
       *
       * Parameters:
       *  (Event) e
       */
      onsubmit: function onsubmit(e) {
        var button = jQuery(this).find(':submit:first');
        button.addClass('loading');
        $.ajax({
          url: jQuery(this).attr('action'),
          data: this.serializeArray(),
          success: function success() {
            button.removeClass('loading');
            // reload current form and tree
            var currNode = $('.cms-tree')[0].firstSelected();
            if (currNode) {
              var url = $(currNode).find('a').attr('href');
              $('.cms-content').loadPanel(url);
            }
            $('.cms-tree')[0].setCustomURL('admin/assets/getsubtree');
            $('.cms-tree')[0].reload({ onSuccess: function onSuccess() {
                // TODO Reset current tree node
              } });
          }
        });

        return false;
      }
    });

    /**
     * Reload the gridfield to show the user the file has been added
     */
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