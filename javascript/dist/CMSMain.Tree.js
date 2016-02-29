(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.CMSMain.Tree', ['jQuery', 'i18n'], factory);
  } else if (typeof exports !== "undefined") {
    factory(require('jQuery'), require('i18n'));
  } else {
    var mod = {
      exports: {}
    };
    factory(global.jQuery, global.i18n);
    global.ssCMSMainTree = mod.exports;
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

  _jQuery2.default.entwine('ss.tree', function ($) {
    $('.cms-tree').entwine({
      fromDocument: {
        'oncontext_show.vakata': function oncontext_showVakata(e) {
          this.adjustContextClass();
        }
      },
      /*
       * Add and remove classes from context menus to allow for
       * adjusting the display
       */
      adjustContextClass: function adjustContextClass() {
        var menus = $('#vakata-contextmenu').find('ul ul');

        menus.each(function (i) {
          var col = '1';
          var count = $(menus[i]).find('li').length;

          // Assign columns to menus over 10 items long
          if (count > 20) {
            col = '3';
          } else if (count > 10) {
            col = '2';
          }

          $(menus[i]).addClass('col-' + col).removeClass('right');

          // Remove "right" class that jstree adds on mouseenter
          $(menus[i]).find('li').on('mouseenter', function (e) {
            $(this).parent('ul').removeClass('right');
          });
        });
      },
      getTreeConfig: function getTreeConfig() {
        var self = this;
        var config = this._super();
        config.plugins.push('contextmenu');
        config.contextmenu = {
          'items': function items(node) {
            var menuitems = {
              'edit': {
                'label': _i18n2.default._t('Tree.EditPage', 'Edit page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
                'action': function action(obj) {
                  $('.cms-container').entwine('.ss').loadPanel(_i18n2.default.sprintf(self.data('urlEditpage'), obj.data('id')));
                }
              }
            };

            // Add "show as list"
            if (!node.hasClass('nochildren')) {
              menuitems['showaslist'] = {
                'label': _i18n2.default._t('Tree.ShowAsList'),
                'action': function action(obj) {
                  $('.cms-container').entwine('.ss').loadPanel(self.data('urlListview') + '&ParentID=' + obj.data('id'), null,
                  // Default to list view tab
                  { tabState: { 'pages-controller-cms-content': { 'tabSelector': '.content-listview' } } });
                }
              };
            }

            // Build a list for allowed children as submenu entries
            var id = node.data('id');
            var allowedChildren = node.find('>a .item').data('allowedchildren');
            var menuAllowedChildren = {};
            var hasAllowedChildren = false;

            // Convert to menu entries
            $.each(allowedChildren, function (klass, title) {
              hasAllowedChildren = true;
              menuAllowedChildren['allowedchildren-' + klass] = {
                'label': '<span class="jstree-pageicon"></span>' + title,
                '_class': 'class-' + klass,
                'action': function action(obj) {
                  $('.cms-container').entwine('.ss').loadPanel($.path.addSearchParams(_i18n2.default.sprintf(self.data('urlAddpage'), id, klass), self.data('extraParams')));
                }
              };
            });

            if (hasAllowedChildren) {
              menuitems['addsubpage'] = {
                'label': _i18n2.default._t('Tree.AddSubPage', 'Add page under this page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
                'submenu': menuAllowedChildren
              };
            }

            menuitems['duplicate'] = {
              'label': _i18n2.default._t('Tree.Duplicate'),
              'submenu': [{
                'label': _i18n2.default._t('Tree.ThisPageOnly'),
                'action': function action(obj) {
                  $('.cms-container').entwine('.ss').loadPanel($.path.addSearchParams(_i18n2.default.sprintf(self.data('urlDuplicate'), obj.data('id')), self.data('extraParams')));
                }
              }, {
                'label': _i18n2.default._t('Tree.ThisPageAndSubpages'),
                'action': function action(obj) {
                  $('.cms-container').entwine('.ss').loadPanel($.path.addSearchParams(_i18n2.default.sprintf(self.data('urlDuplicatewithchildren'), obj.data('id')), self.data('extraParams')));
                }
              }]
            };

            return menuitems;
          }
        };
        return config;
      }
    });

    // Scroll tree down to context of the current page, if it isn't
    // already visible
    $('.cms-tree a.jstree-clicked').entwine({
      onmatch: function onmatch() {
        var self = this;
        var panel = self.parents('.cms-panel-content');
        var scrollTo;

        if (self.offset().top < 0 || self.offset().top > panel.height() - self.height()) {
          // Current scroll top + our current offset top is our
          // position in the panel
          scrollTo = panel.scrollTop() + self.offset().top + panel.height() / 2;

          panel.animate({
            scrollTop: scrollTo
          }, 'slow');
        }
      }
    });

    // Clear filters button
    $('.cms-tree-filtered .clear-filter').entwine({
      onclick: function onclick() {
        window.location = document.location.protocol + '//' + document.location.host + document.location.pathname;
      }
    });

    $('.cms-tree-filtered').entwine({
      onmatch: function onmatch() {
        var self = this;
        var setHeight = function setHeight() {
          var height = $('.cms-content-tools .cms-panel-content').height() - self.parent().siblings('.cms-content-toolbar').outerHeight(true);
          self.css('height', height + 'px');
        };

        setHeight();
        $(window).on('resize', window.ss.debounce(setHeight, 300));
      }
    });
  });
});