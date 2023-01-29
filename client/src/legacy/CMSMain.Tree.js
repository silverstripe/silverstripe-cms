import $ from 'jquery';
import i18n from 'i18n';
import reactConfirm from 'reactstrap-confirm';
import { joinUrlPaths } from 'lib/urls';

$.entwine('ss.tree', function($) {
  $('.cms-tree').entwine({
    fromDocument: {
      'oncontext_show.vakata': function(e) {
        this.adjustContextClass();
      }
    },
    /*
     * Add and remove classes from context menus to allow for
     * adjusting the display
     */
    adjustContextClass: function() {
      var menus = $('#vakata-contextmenu').find("ul ul");

      menus.each(function(i) {
        var col = "1",
            count = $(menus[i]).find('li').length;

        //Assign columns to menus over 10 items long
        if (count > 20) {
          col = "3";
        } else if (count > 10) {
          col = "2";
        }

        $(menus[i]).addClass('vakata-col-' + col).removeClass('right');

        //Remove "right" class that jstree adds on mouseenter
        $(menus[i]).find('li').on("mouseenter", function(e) {
          $(this).parent('ul').removeClass("right");
        });
      });
    },

    showListViewFor: function(id) {
      localStorage.setItem('ss.pages-view-type', 'listview');
      const $contentView = this.closest('.cms-content-view');
      const url = $contentView.data('url-listviewroot');
      const urlWithParams = $.path.addSearchParams(url, {
        ParentID: id
      });

      const baseUrl = $('base').attr('href') || ''; // Edge17 and IE11 require absolute paths
      window.location.assign(joinUrlPaths(baseUrl, urlWithParams));
    },

    getTreeConfig: function() {
      var self = this,
          config = this._super(),
          hints = this.getHints();
      config.plugins.push('contextmenu');
      config.contextmenu = {
        'items': function(node) {

          var menuitems = {
            edit: {
              'label': (node.hasClass('edit-disabled')) ?
                i18n._t('CMS.EditPage', 'Edit page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree') :
                i18n._t('CMS.ViewPage', 'View page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
              'action': function(obj) {
                $('.cms-container').entwine('.ss').loadPanel(i18n.sprintf(
                  self.data('urlEditpage'), obj.data('id')
                ));
              }
            }
          };

          // Add "show as list"
          if (!node.hasClass('nochildren')) {
            menuitems['showaslist'] = {
              'label': i18n._t('CMS.ShowAsList'),
              'action': function(obj) {
                self.showListViewFor(obj.data('id'));
              }
            };
          }

          // Build a list for allowed children as submenu entries
          var pagetype = node.data('pagetype'),
              id = node.data('id'),
              allowedChildren = node.find('>a .item').data('allowedchildren'),
              menuAllowedChildren = {},
              hasAllowedChildren = false;

          // Convert to menu entries
          $.each(allowedChildren, function(index, child) {
            hasAllowedChildren = true;
            menuAllowedChildren["allowedchildren-" + child.ClassName] = {
              'label': '<span class="jstree-pageicon ' + child.IconClass + '"></span>' + child.Title,
              '_class': 'class-' + child.ClassName.replace(/[^a-zA-Z0-9\-_:.]+/g, '_'),
              'action': function(obj) {
                $('.cms-container').entwine('.ss').loadPanel(
                  $.path.addSearchParams(
                    i18n.sprintf(self.data('urlAddpage'), id, child.ClassName),
                    self.data('extraParams')
                  )
                );
              }
            };
          });

          if (hasAllowedChildren) {
            menuitems['addsubpage'] = {
              'label': i18n._t('CMS.AddSubPage', 'Add page under this page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
              'submenu': menuAllowedChildren
            };
          }

          if (!node.hasClass('edit-disabled')) {
            menuitems['duplicate'] = {
              'label': i18n._t('CMS.Duplicate'),
              'submenu': [{
                'label': i18n._t('CMS.ThisPageOnly'),
                'action': function(obj) {
                  $('.cms-container').entwine('.ss').loadPanel(
                    $.path.addSearchParams(
                      i18n.sprintf(self.data('urlDuplicate'), obj.data('id')),
                      self.data('extraParams')
                    )
                  );
                }
              }, {
                'label': i18n._t('CMS.ThisPageAndSubpages'),
                'action': function(obj) {
                  $('.cms-container').entwine('.ss').loadPanel(
                    $.path.addSearchParams(
                      i18n.sprintf(self.data('urlDuplicatewithchildren'), obj.data('id')),
                      self.data('extraParams')
                    )
                  );
                }
              }]
            };
          }

          return menuitems;
        }
      };
      return config;
    },

    /**
     * Validates the moving of a node
     * @param {Object} data provided by the move_node.jstree event
     * @returns {Promise<boolean>} Returning false will prevent the node from moving
     */
    canMove: async function (data) {
      // only display warning if its the homepage
      var isHomePage = data.rslt.o.find(".homepage").first().length > 0;
      if (!isHomePage) {
        return true;
      }

      // only display warning if we're moving to a new parent
      var oldParentId = data.rslt.op.data('id');
      var newParentId = data.rslt.np.data('id');
      if (oldParentId === newParentId) {
        return true;
      }

      var message = i18n._t(
        'CMS.RemoveHomePageWarningMessage',
        'Warning: This page is the home page. ' +
        'By changing the URL segment visitors will not be able to view it.'
      );

      return await reactConfirm({
        title: i18n._t(
          'CMS.RemoveHomePageWarningTitle',
          'Remove your home page?'
        ),
        message,
        confirmText: i18n._t(
          'CMS.RemoveHomePageWarningLabel',
          'Remove'
        ),
        confirmColor: 'danger'
      });
    }
  });

  // Scroll tree down to context of the current page, if it isn't
  // already visible
  $('.cms-tree a.jstree-clicked').entwine({
    onmatch: function() {
      var self = this,
          panel = self.parents('.cms-tree-view-sidebar');

      if (self.offset().top < 0 ||
          self.offset().top > panel.height() - self.height()) {
        var scrollToElement = self.parent();
        // scroll to the list item above for some extra padding if possible
        if (scrollToElement.prev().length) {
          scrollToElement = scrollToElement.prev();
        }
        scrollToElement.get(0).scrollIntoView();
      }
    }
  });

  // Clear filters button
  $('.cms-tree-filtered .clear-filter').entwine({
    onclick: function() {
      window.location = location.protocol + '//' + location.host + location.pathname;
    }
  });

  $('.cms-tree .subtree-list-link').entwine({
    onclick: function(e) {
      e.preventDefault();
      this.closest('.cms-tree').showListViewFor(this.data('id'));
    }
  });
});
