import $ from 'jquery';
import i18n from 'i18n';

/**
 * File: CMSPageHistoryController.js
 *
 * Handles related interactions between the version selection form on the
 * left hand side of the panel and the version displaying on the right
 * hand side.
 */
$.entwine('ss', ($) => { // eslint-disable-line no-shadow
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
    onmatch() {
      this._super();
    },
    onunmatch() {
      this._super();
    },
    /**
     * Function: submit.
     *
     * Submits either the compare versions form or the view single form
     * display based on whether we have two or 1 option selected
     *
     * Todo:
     *    Handle coupling to admin url
     */
    onsubmit(e) {
      e.preventDefault();
      const id = this.find(':input[name=ID]').val();

      if (!id) {
        return false;
      }

      let url = null;
      let to = null;
      let from = null;

      const compare = (this.find(':input[name=CompareMode]').is(':checked'));
      const selected = this.find('table input[type=checkbox]').filter(':checked');

      if (compare) {
        if (selected.length !== 2) {
          return false;
        }

        to = selected.eq(0).val();
        from = selected.eq(1).val();
        url = i18n.sprintf(this.data('linkTmplCompare'), id, from, to);
      } else {
        to = selected.eq(0).val();
        url = i18n.sprintf(this.data('linkTmplShow'), id, to);
      }

      $('.cms-container').loadPanel(url, '', { pjax: 'CurrentForm' });
      return true;
    },
  });

  /**
   * Class: :input[name=ShowUnpublished]
   *
   * Used for toggling whether to show or hide unpublished versions.
   */
  $('#Form_VersionsForm input[name=ShowUnpublished]').entwine({
    onmatch() {
      this.toggle();
      this._super();
    },
    onunmatch() {
      this._super();
    },
    /**
     * Event: :input[name=ShowUnpublished] change
     *
     * Changing the show unpublished checkbox toggles whether to show
     * or hide the unpublished versions. Because those rows may be being
     * compared this also ensures those rows are unselected.
     */
    onchange() {
      this.toggle();
    },
    toggle() {
      const self = $(this);
      const unpublished = self.parents('form').find('tr[data-published=false]');

      if (self.attr('checked')) {
        unpublished
          .removeClass('ui-helper-hidden')
          .show();
      } else {
        unpublished
          .addClass('ui-helper-hidden')
          .hide()
          ._unselect();
      }
    },
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
    onclick() {
      // compare mode
      const compare = this.parents('form').find(':input[name=CompareMode]').attr('checked');
      const selected = this.siblings('.active');

      if (compare && this.hasClass('active')) {
        this._unselect();
        return;
      }

      if (compare) {
        // check if we have already selected more than two.
        if (selected.length > 1) {
          // eslint-disable-next-line no-alert
          alert(i18n._t('CMS.ONLYSELECTTWO', 'You can only compare two versions at this time.'));
          return;
        }

        this._select();

        // if this is the second selected then we can compare.
        if (selected.length === 1) {
          this.parents('form').submit();
        }
        return;
      }
      this._select();
      selected._unselect();
      this.parents('form').submit();
    },

    /**
     * Function: _unselect()
     *
     * Unselects the row from the form selection.
     *
     * Using regular js to update the class rather than this.removeClass('active')
     * because the latter causes the browser to continuously call
     * element.compareDocumentPosition, causing the browser to hang for long
     * periods of time, especially on pages with lots of versions (e.g. 100+)
     */
    _unselect() {
      this.get(0).classList.remove('active');
      this.find(':input[type=checkbox][checked]').attr('checked', false);
    },

    /**
     * Function: _select()
     *
     * Selects the currently matched row in the form selection
     */
    _select() {
      this.addClass('active');
      this.find(':input[type=checkbox]').attr('checked', true);
    },

  });
});
