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

  /**
   * File: CMSPageHistoryController.js
   *
   * Handles related interactions between the version selection form on the
   * left hand side of the panel and the version displaying on the right
   * hand side.
   */

  _jQuery2.default.entwine('ss', function ($) {
    /**
     * Class: #Form_VersionsForm
     *
     * The left hand side version selection form is the main interface for
     * users to select a version to view, or to compare two versions
     */
    $('#Form_VersionsForm').entwine({
      /**
       * Constructor
       */
      onmatch: function onmatch() {
        this._super();
      },
      onunmatch: function onunmatch() {
        this._super();
      },
      /**
       * Function: submit.
       *
       * Submits either the compare versions form or the view single form
       * display based on whether we have two or 1 option selected
       *
       * Todo:
       *		Handle coupling to admin url
       */
      onsubmit: function onsubmit(e, d) {
        e.preventDefault();

        var id = this.find(':input[name=ID]').val();
        var url;
        var selected;
        var to;
        var from;
        var compare;

        if (!id) return false;

        compare = this.find(':input[name=CompareMode]').is(':checked');
        selected = this.find('table input[type=checkbox]').filter(':checked');

        if (compare) {
          if (selected.length !== 2) return false;

          to = selected.eq(0).val();
          from = selected.eq(1).val();
          url = _i18n2.default.sprintf(this.data('linkTmplCompare'), id, from, to);
        } else {
          to = selected.eq(0).val();
          url = _i18n2.default.sprintf(this.data('linkTmplShow'), id, to);
        }

        $('.cms-container').loadPanel(url, '', { pjax: 'CurrentForm' });
      }
    });

    /**
     * Class: :input[name=ShowUnpublished]
     *
     * Used for toggling whether to show or hide unpublished versions.
     */
    $('#Form_VersionsForm input[name=ShowUnpublished]').entwine({
      onmatch: function onmatch() {
        this.toggle();
        this._super();
      },
      onunmatch: function onunmatch() {
        this._super();
      },
      /**
       * Event: :input[name=ShowUnpublished] change
       *
       * Changing the show unpublished checkbox toggles whether to show
       * or hide the unpublished versions. Because those rows may be being
       * compared this also ensures those rows are unselected.
       */
      onchange: function onchange() {
        this.toggle();
      },
      toggle: function toggle() {
        var self = $(this);
        var form = self.parents('form');

        if (self.attr('checked')) {
          form.find('tr[data-published=false]').show();
        } else {
          form.find('tr[data-published=false]').hide()._unselect();
        }
      }
    });

    /**
     * Class: #Form_VersionsForm tr
     *
     * An individual row in the versions form. Selecting the row updates
     * the edit form depending on whether we're showing individual version
     * information or displaying comparsion.
     */
    $('#Form_VersionsForm tbody tr').entwine({
      /**
       * Function: onclick
       *
       * Selects or deselects the row (if in compare mode). Will trigger
       * an update of the edit form if either selected (in single mode)
       * or if this is the second row selected (in compare mode)
       */
      onclick: function onclick(e) {
        var compare, selected;

        // compare mode
        compare = this.parents('form').find(':input[name=CompareMode]').attr('checked');
        selected = this.siblings('.active');

        if (compare && this.hasClass('active')) {
          this._unselect();

          return;
        } else if (compare) {
          // check if we have already selected more than two.
          if (selected.length > 1) {
            return window.alert(_i18n2.default._t('ONLYSELECTTWO', 'You can only compare two versions at this time.'));
          }

          this._select();

          // if this is the second selected then we can compare.
          if (selected.length === 1) {
            this.parents('form').submit();
          }

          return;
        } else {
          this._select();
          selected._unselect();

          this.parents('form').submit();
        }
      },

      /**
       * Function: _unselect()
       *
       * Unselects the row from the form selection.
       */
      _unselect: function _unselect() {
        this.removeClass('active');
        this.find(':input[type=checkbox]').attr('checked', false);
      },

      /**
       * Function: _select()
       *
       * Selects the currently matched row in the form selection
       */
      _select: function _select() {
        this.addClass('active');
        this.find(':input[type=checkbox]').attr('checked', true);
      }

    });
  });
});