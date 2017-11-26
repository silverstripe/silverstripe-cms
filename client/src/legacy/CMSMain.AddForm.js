import $ from 'jquery';

$.entwine('ss', function($){
  $('.TreeDropdownField').entwine({
    OldValue: null,
  });

  $('#Form_AddForm_ParentID_Holder .treedropdownfield').entwine({
    onmatch() {
      this._super();
      $('.cms-add-form').updateTypeList();
    }
  });

  /**
   * Reset the parent node selection if the type is set back to "toplevel page",
   * to avoid submitting inconsistent state.
   */
  $(".cms-add-form .parent-mode :input").entwine({
    onclick: function(e) {
      var parentField = this.closest('form').find('#Form_AddForm_ParentID_Holder .TreeDropdownField');
      if(this.val() == 'top') {
        parentField.setOldValue(parentField.getValue());
        parentField.setValue(0);
      } else {
        parentField.setValue(parentField.getOldValue() || 0);
        parentField.setOldValue(null);
      }
      parentField.refresh();
      parentField.trigger('change');
    }
  });

  $(".cms-add-form").entwine({
    ParentCache: {}, // Cache allowed children for each selected page
    onadd: function() {
      var self = this;
      // See SelectionGroup.js for logic which hides / shows ParentID
      this.find('#Form_AddForm_ParentID_Holder .TreeDropdownField').bind('change', function() {
        self.updateTypeList();
      });
      this.find(".SelectionGroup.parent-mode").bind('change',  function() {
        self.updateTypeList();
      });
      if ($(".cms-add-form .parent-mode :input").val() == 'top') {
        this.updateTypeList();
      }
    },
    loadCachedChildren: function(parentID) {
      var cache = this.getParentCache();
      if(typeof cache[parentID] !== 'undefined') {
        return cache[parentID];
      }
      return null;
    },
    saveCachedChildren: function(parentID, children) {
      var cache = this.getParentCache();
      cache[parentID] = children;
      this.setParentCache(cache);
    },
    /**
     * Limit page type selection based on parent selection.
     * Select of root classes is pre-computed, but selections with a given parent
     * are updated on-demand.
     * Similar implementation to LeftAndMain.Tree.js.
     */
    updateTypeList: function() {
      var hints = this.data('hints'),
        parentTree = this.find('#Form_AddForm_ParentID'),
        parentMode = this.find("input[name=ParentModeField]:checked").val(),
        metadata = parentTree.data('metadata'),
        id = (parentMode === 'child')
          ? parentTree.getValue()
          : null,
        newClassName = metadata ? metadata.ClassName : null,
        hintKey = (newClassName && parentMode === 'child' && id)
          ? newClassName
          : 'Root',
        hint = (typeof hints[hintKey] !== 'undefined') ? hints[hintKey] : null,
        self = this,
        defaultChildClass = (hint && typeof hint.defaultChild !== 'undefined')
          ? hint.defaultChild
          : null,
        disallowedChildren = [];

      if(id) {
        // Prevent interface operations
        if(this.hasClass('loading')) return;
        this.addClass('loading');

        // Use cached data if available
        disallowedChildren = this.loadCachedChildren(id);
        if(disallowedChildren !== null) {
          this.updateSelectionFilter(disallowedChildren, defaultChildClass);
          this.removeClass('loading');
          return;
        }
        $.ajax({
          url: self.data('childfilter'),
          data: {'ParentID': id},
          success: function(data) {
            // reload current form and tree
            self.saveCachedChildren(id, data);
            self.updateSelectionFilter(data, defaultChildClass);
          },
          complete: function() {
            self.removeClass('loading');
          }
        });

        return false;
      } else {
        disallowedChildren = (hint && typeof hint.disallowedChildren !== 'undefined')
          ? hint.disallowedChildren
          : [];
        this.updateSelectionFilter(disallowedChildren, defaultChildClass);
      }
    },
    /**
     * Update the selection filter with the given blacklist and default selection
     *
     * @param {Array} disallowedChildren
     * @param {string} defaultChildClass
     */
    updateSelectionFilter: function(disallowedChildren, defaultChildClass) {
      // Limit selection
      var allAllowed = null; // troolian
      this.find('#Form_AddForm_PageType div.radio').each(function() {
        var className = $(this).find('input').val(),
          isAllowed = ($.inArray(className, disallowedChildren) === -1);

        $(this).setEnabled(isAllowed);
        if(!isAllowed) {
          $(this).setSelected(false);
        }
        if(allAllowed === null) {
          allAllowed = isAllowed;
        } else {
          allAllowed = allAllowed && isAllowed;
        }
      });

      // Set default child selection, or fall back to first available option
      if(defaultChildClass) {
        var selectedEl = this
          .find('#Form_AddForm_PageType div.radio input[value=' + defaultChildClass + ']')
          .parents('li:first');
      } else {
        var selectedEl = this.find('#Form_AddForm_PageType div.radio:not(.disabled):first');
      }
      selectedEl.setSelected(true);
      selectedEl.siblings().setSelected(false);

      // Disable the "Create" button if none of the pagetypes are available
      if(this.find('#Form_AddForm_PageType div.radio:not(.disabled)').length) {
        this.find('button[name=action_doAdd]').removeAttr('disabled');
      } else {
        this.find('button[name=action_doAdd]').attr('disabled', 'disabled');
      }

      this.find('.message-restricted')[allAllowed ? 'hide' : 'show']();
    }
  });

  $(".cms-add-form #Form_AddForm_PageType div.radio").entwine({
    onclick: function(e) {
      this.setSelected(true);
    },
    setSelected: function(bool) {
      var input = this.find('input');
      if(bool && !input.is(':disabled')) {
        this.siblings().setSelected(false);
        this.toggleClass('selected', true);
        input.prop('checked', true);
      } else {
        this.toggleClass('selected', false);
        input.prop('checked', false);
      }
    },
    setEnabled: function(bool) {
      $(this).toggleClass('disabled', !bool);
      if(!bool) {
        $(this).find('input').attr('disabled',  'disabled').removeAttr('checked');
      } else {
        $(this).find('input').removeAttr('disabled');
      }
    }
  });

  $(".cms-content-addpage-button").entwine({
    onclick: function(e) {
      var tree = $('.cms-tree'), list = $('.cms-list'), parentId = 0, extraParams;

      // Choose parent ID either from tree or list view, depending which is visible
      if(tree.is(':visible')) {
        var selected = tree.jstree('get_selected');
        parentId = selected ? $(selected[0]).data('id') : null;
      } else {
        var state = list.find('input[name="Page[GridState]"]').val();
        if(state) {
          parentId = parseInt(JSON.parse(state).ParentID, 10);
        }
      }

      var data = {selector: this.data('targetPanel'),pjax: this.data('pjax')}, url;
      if(parentId) {
        extraParams = this.data('extraParams') ? this.data('extraParams') : '';
        url = $.path.addSearchParams(i18n.sprintf(this.data('urlAddpage'), parentId), extraParams);
      } else {
        url = this.attr('href');
      }
      $('.cms-container').loadPanel(url, null, data);
      e.preventDefault();

      // Remove focussed state from button
      this.blur();

      // $('.cms-page-add-form-dialog').dialog('open');
      // e.preventDefault();
    }
  });
});
