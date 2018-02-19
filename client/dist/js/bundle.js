/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// identity function for calling harmony imports with the correct context
/******/ 	__webpack_require__.i = function(value) { return value; };
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 22);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports) {

module.exports = jQuery;

/***/ }),
/* 1 */
/***/ (function(module, exports) {

module.exports = i18n;

/***/ }),
/* 2 */
/***/ (function(module, exports) {

module.exports = Injector;

/***/ }),
/* 3 */
/***/ (function(module, exports) {

module.exports = React;

/***/ }),
/* 4 */,
/* 5 */,
/* 6 */,
/* 7 */,
/* 8 */,
/* 9 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = {
  ANCHORSELECTOR_UPDATED: 'ANCHORSELECTOR_UPDATED',
  ANCHORSELECTOR_UPDATING: 'ANCHORSELECTOR_UPDATING',
  ANCHORSELECTOR_UPDATE_FAILED: 'ANCHORSELECTOR_UPDATE_FAILED'
};

/***/ }),
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = {
  SUCCESS: 'SUCCESS',
  DIRTY: 'DIRTY',
  UPDATING: 'UPDATING',
  FAILED: 'FAILED'
};

/***/ }),
/* 11 */
/***/ (function(module, exports) {

module.exports = Redux;

/***/ }),
/* 12 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _registerReducers = __webpack_require__(21);

var _registerReducers2 = _interopRequireDefault(_registerReducers);

var _registerComponents = __webpack_require__(20);

var _registerComponents2 = _interopRequireDefault(_registerComponents);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

window.document.addEventListener('DOMContentLoaded', function () {
  (0, _registerComponents2.default)();
  (0, _registerReducers2.default)();
});

/***/ }),
/* 13 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jquery2.default.entwine('ss', function ($) {
  $('.TreeDropdownField').entwine({
    OldValue: null
  });

  $('#Form_AddForm_ParentID_Holder .treedropdownfield').entwine({
    onmatch: function onmatch() {
      this._super();
      $('.cms-add-form').updateTypeList();
    }
  });

  $(".cms-add-form .parent-mode :input").entwine({
    onclick: function onclick(e) {
      var parentField = this.closest('form').find('#Form_AddForm_ParentID_Holder .TreeDropdownField');
      if (this.val() == 'top') {
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
    ParentCache: {},
    onadd: function onadd() {
      var self = this;

      this.find('#Form_AddForm_ParentID_Holder .TreeDropdownField').bind('change', function () {
        self.updateTypeList();
      });
      this.find(".SelectionGroup.parent-mode").bind('change', function () {
        self.updateTypeList();
      });
      if ($(".cms-add-form .parent-mode :input").val() == 'top') {
        this.updateTypeList();
      }
    },
    loadCachedChildren: function loadCachedChildren(parentID) {
      var cache = this.getParentCache();
      if (typeof cache[parentID] !== 'undefined') {
        return cache[parentID];
      }
      return null;
    },
    saveCachedChildren: function saveCachedChildren(parentID, children) {
      var cache = this.getParentCache();
      cache[parentID] = children;
      this.setParentCache(cache);
    },

    updateTypeList: function updateTypeList() {
      var hints = this.data('hints'),
          parentTree = this.find('#Form_AddForm_ParentID'),
          parentMode = this.find("input[name=ParentModeField]:checked").val(),
          metadata = parentTree.data('metadata'),
          id = parentMode === 'child' ? parentTree.getValue() : null,
          newClassName = metadata ? metadata.ClassName : null,
          hintKey = newClassName && parentMode === 'child' && id ? newClassName : 'Root',
          hint = typeof hints[hintKey] !== 'undefined' ? hints[hintKey] : null,
          self = this,
          defaultChildClass = hint && typeof hint.defaultChild !== 'undefined' ? hint.defaultChild : null,
          disallowedChildren = [];

      if (id) {
        if (this.hasClass('loading')) return;
        this.addClass('loading');

        disallowedChildren = this.loadCachedChildren(id);
        if (disallowedChildren !== null) {
          this.updateSelectionFilter(disallowedChildren, defaultChildClass);
          this.removeClass('loading');
          return;
        }
        $.ajax({
          url: self.data('childfilter'),
          data: { 'ParentID': id },
          success: function success(data) {
            self.saveCachedChildren(id, data);
            self.updateSelectionFilter(data, defaultChildClass);
          },
          complete: function complete() {
            self.removeClass('loading');
          }
        });

        return false;
      } else {
        disallowedChildren = hint && typeof hint.disallowedChildren !== 'undefined' ? hint.disallowedChildren : [];
        this.updateSelectionFilter(disallowedChildren, defaultChildClass);
      }
    },

    updateSelectionFilter: function updateSelectionFilter(disallowedChildren, defaultChildClass) {
      var allAllowed = null;
      this.find('#Form_AddForm_PageType div.radio').each(function () {
        var className = $(this).find('input').val(),
            isAllowed = $.inArray(className, disallowedChildren) === -1;

        $(this).setEnabled(isAllowed);
        if (!isAllowed) {
          $(this).setSelected(false);
        }
        if (allAllowed === null) {
          allAllowed = isAllowed;
        } else {
          allAllowed = allAllowed && isAllowed;
        }
      });

      if (defaultChildClass) {
        var selectedEl = this.find('#Form_AddForm_PageType div.radio input[value=' + defaultChildClass + ']').parents('li:first');
      } else {
        var selectedEl = this.find('#Form_AddForm_PageType div.radio:not(.disabled):first');
      }
      selectedEl.setSelected(true);
      selectedEl.siblings().setSelected(false);

      if (this.find('#Form_AddForm_PageType div.radio:not(.disabled)').length) {
        this.find('button[name=action_doAdd]').removeAttr('disabled');
      } else {
        this.find('button[name=action_doAdd]').attr('disabled', 'disabled');
      }

      this.find('.message-restricted')[allAllowed ? 'hide' : 'show']();
    }
  });

  $(".cms-add-form #Form_AddForm_PageType div.radio").entwine({
    onclick: function onclick(e) {
      this.setSelected(true);
    },
    setSelected: function setSelected(bool) {
      var input = this.find('input');
      if (bool && !input.is(':disabled')) {
        this.siblings().setSelected(false);
        this.toggleClass('selected', true);
        input.prop('checked', true);
      } else {
        this.toggleClass('selected', false);
        input.prop('checked', false);
      }
    },
    setEnabled: function setEnabled(bool) {
      $(this).toggleClass('disabled', !bool);
      if (!bool) {
        $(this).find('input').attr('disabled', 'disabled').removeAttr('checked');
      } else {
        $(this).find('input').removeAttr('disabled');
      }
    }
  });

  $(".cms-content-addpage-button").entwine({
    onclick: function onclick(e) {
      var tree = $('.cms-tree'),
          list = $('.cms-list'),
          parentId = 0,
          extraParams;

      if (tree.is(':visible')) {
        var selected = tree.jstree('get_selected');
        parentId = selected ? $(selected[0]).data('id') : null;
      } else {
        var state = list.find('input[name="Page[GridState]"]').val();
        if (state) {
          parentId = parseInt(JSON.parse(state).ParentID, 10);
        }
      }

      var data = { selector: this.data('targetPanel'), pjax: this.data('pjax') },
          url;
      if (parentId) {
        extraParams = this.data('extraParams') ? this.data('extraParams') : '';
        url = $.path.addSearchParams(i18n.sprintf(this.data('urlAddpage'), parentId), extraParams);
      } else {
        url = this.attr('href');
      }
      $('.cms-container').loadPanel(url, null, data);
      e.preventDefault();

      this.blur();
    }
  });
});

/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _i18n = __webpack_require__(1);

var _i18n2 = _interopRequireDefault(_i18n);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jquery2.default.entwine('ss', function ($) {
	$('.cms-edit-form :input[name=ClassName]').entwine({
		onchange: function onchange() {
			alert(_i18n2.default._t('CMS.ALERTCLASSNAME'));
		}
	});

	$('.cms-edit-form input[name=Title]').entwine({
		onmatch: function onmatch() {
			var self = this;

			self.data('OrigVal', self.val());

			var form = self.closest('form');
			var urlSegmentInput = $('input:text[name=URLSegment]', form);
			var liveLinkInput = $('input[name=LiveLink]', form);

			if (urlSegmentInput.length > 0) {
				self._addActions();
				this.bind('change', function (e) {
					var origTitle = self.data('OrigVal');
					var title = self.val();
					self.data('OrigVal', title);

					if (urlSegmentInput.val().indexOf(urlSegmentInput.data('defaultUrl')) === 0 && liveLinkInput.val() == '') {
						self.updateURLSegment(title);
					} else {
						$('.update', self.parent()).show();
					}

					self.updateRelatedFields(title, origTitle);
					self.updateBreadcrumbLabel(title);
				});
			}

			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		},

		updateRelatedFields: function updateRelatedFields(title, origTitle) {
			this.parents('form').find('input[name=MetaTitle], input[name=MenuTitle]').each(function () {
				var $this = $(this);
				if ($this.val() == origTitle) {
					$this.val(title);

					if ($this.updatedRelatedFields) $this.updatedRelatedFields();
				}
			});
		},

		updateURLSegment: function updateURLSegment(title) {
			var urlSegmentInput = $('input:text[name=URLSegment]', this.closest('form'));
			var urlSegmentField = urlSegmentInput.closest('.field.urlsegment');
			var updateURLFromTitle = $('.update', this.parent());
			urlSegmentField.update(title);
			if (updateURLFromTitle.is(':visible')) {
				updateURLFromTitle.hide();
			}
		},

		updateBreadcrumbLabel: function updateBreadcrumbLabel(title) {
			var pageID = $('.cms-edit-form input[name=ID]').val();
			var panelCrumb = $('span.cms-panel-link.crumb');
			if (title && title != "") {
				panelCrumb.text(title);
			}
		},

		_addActions: function _addActions() {
			var self = this;
			var updateURLFromTitle;

			updateURLFromTitle = $('<button />', {
				'class': 'update btn btn-outline-secondary form__field-update-url',
				'text': _i18n2.default._t('CMS.UpdateURL'),
				'type': 'button',
				'click': function click(e) {
					e.preventDefault();
					self.updateURLSegment(self.val());
				}
			});

			updateURLFromTitle.insertAfter(self);
			updateURLFromTitle.parent('.form__field-holder').addClass('input-group');
			updateURLFromTitle.hide();
		}
	});

	$('.cms-edit-form .parentTypeSelector').entwine({
		onmatch: function onmatch() {
			var self = this;
			this.find(':input[name=ParentType]').bind('click', function (e) {
				self._toggleSelection(e);
			});
			this.find('.TreeDropdownField').bind('change', function (e) {
				self._changeParentId(e);
			});

			this._changeParentId();
			this._toggleSelection();

			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		},

		_toggleSelection: function _toggleSelection(e) {
			var selected = this.find(':input[name=ParentType]:checked').val(),
			    holder = this.find('#Form_EditForm_ParentID_Holder');

			if (selected == 'root') this.find(':input[name=ParentID]').val(0);else this.find(':input[name=ParentID]').val(this.find('#Form_EditForm_ParentType_subpage').data('parentIdValue'));

			if (selected != 'root') {
				holder.slideDown(400, function () {
					$(this).css('overflow', 'visible');
				});
			} else {
				holder.slideUp();
			}
		},

		_changeParentId: function _changeParentId(e) {
			var value = this.find(':input[name=ParentID]').val();

			this.find('#Form_EditForm_ParentType_subpage').data('parentIdValue', value);
		}
	});

	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_doRollback, .cms-edit-form .btn-toolbar #Form_EditForm_action_rollback').entwine({
		onclick: function onclick(e) {
			var form = this.parents('form:first'),
			    version = form.find(':input[name=Version]').val(),
			    message = '';
			if (this.props('disabled')) {
				return false;
			}
			if (version) {
				message = _i18n2.default.sprintf(_i18n2.default._t('CMS.RollbackToVersion', 'Do you really want to roll back to version #%s of this page?'), version);
			} else {
				message = _i18n2.default._t('CMS.ConfirmRestoreFromLive', 'Are you sure you want to revert draft to when the page was last published?');
			}
			if (confirm(message)) {
				return this._super(e);
			} else {
				return false;
			}
		}
	});

	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_archive').entwine({
		onclick: function onclick(e) {
			var form = this.parents('form:first'),
			    message = '';
			message = form.find('input[name=ArchiveWarningMessage]').val().replace(/\\n/g, '\n');

			if (confirm(message)) {
				return this._super(e);
			} else {
				return false;
			}
		}
	});

	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_restore').entwine({
		onclick: function onclick(e) {
			var form = this.parents('form:first'),
			    version = form.find(':input[name=Version]').val(),
			    message = '',
			    toRoot = this.data('toRoot');
			message = _i18n2.default.sprintf(_i18n2.default._t(toRoot ? 'CMS.RestoreToRoot' : 'CMS.Restore'), version);
			if (confirm(message)) {
				return this._super(e);
			} else {
				return false;
			}
		}
	});

	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_unpublish').entwine({
		onclick: function onclick(e) {
			var form = this.parents('form:first'),
			    version = form.find(':input[name=Version]').val(),
			    message = '';
			message = _i18n2.default.sprintf(_i18n2.default._t('CMS.Unpublish'), version);
			if (confirm(message)) {
				return this._super(e);
			} else {
				return false;
			}
		}
	});

	$('.cms-edit-form.changed').entwine({
		onmatch: function onmatch(e) {
			this.find('button[data-text-alternate]').each(function () {
				var button = $(this);
				var buttonTitle = button.find('.btn__title');

				var alternateText = button.data('textAlternate');
				if (alternateText) {
					button.data('textStandard', buttonTitle.text());
					buttonTitle.text(alternateText);
				}

				var alternateClasses = button.data('btnAlternate');
				if (alternateClasses) {
					button.data('btnStandard', button.attr('class'));
					button.attr('class', alternateClasses);
					button.removeClass('btn-outline-secondary').addClass('btn-primary');
				}

				var alternateClassesAdd = button.data('btnAlternateAdd');
				if (alternateClassesAdd) {
					button.addClass(alternateClassesAdd);
				}
				var alternateClassesRemove = button.data('btnAlternateRemove');
				if (alternateClassesRemove) {
					button.removeClass(alternateClassesRemove);
				}
			});

			this._super(e);
		},
		onunmatch: function onunmatch(e) {
			this.find('button[data-text-alternate]').each(function () {
				var button = $(this);
				var buttonTitle = button.find('.btn__title');

				var standardText = button.data('textStandard');
				if (standardText) {
					buttonTitle.text(standardText);
				}

				var standardClasses = button.data('btnStandard');
				if (standardClasses) {
					button.attr('class', standardClasses);
					button.addClass('btn-outline-secondary').removeClass('btn-primary');
				}

				var alternateClassesAdd = button.data('btnAlternateAdd');
				if (alternateClassesAdd) {
					button.removeClass(alternateClassesAdd);
				}
				var alternateClassesRemove = button.data('btnAlternateRemove');
				if (alternateClassesRemove) {
					button.addClass(alternateClassesRemove);
				}
			});

			this._super(e);
		}
	});

	$('.cms-edit-form .btn-toolbar button[name=action_publish]').entwine({
		onbuttonafterrefreshalternate: function onbuttonafterrefreshalternate() {
			if (this.data('showingAlternate')) {
				this.addClass('btn-primary');
				this.removeClass('btn-secondary');
			} else {
				this.removeClass('btn-primary');
				this.addClass('btn-secondary');
			}
		}
	});

	$('.cms-edit-form .btn-toolbar button[name=action_save]').entwine({
		onbuttonafterrefreshalternate: function onbuttonafterrefreshalternate() {
			if (this.data('showingAlternate')) {
				this.addClass('btn-primary');
				this.removeClass('btn-secondary');
			} else {
				this.removeClass('btn-primary');
				this.addClass('btn-secondary');
			}
		}
	});

	$('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').entwine({
		onmatch: function onmatch() {
			this.redraw();
			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		},
		redraw: function redraw() {
			var treeField = $('.cms-edit-form.CMSPageSettingsController #Form_EditForm_ParentID_Holder');
			if ($(this).attr('id') == 'Form_EditForm_ParentType_root') treeField.slideUp();else treeField.slideDown();
		},
		onclick: function onclick() {
			this.redraw();
		}
	});

	if ($('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').attr('id') == 'Form_EditForm_ParentType_root') {
		$('.cms-edit-form.CMSPageSettingsController #Form_EditForm_ParentID_Holder').hide();
	}
});

/***/ }),
/* 15 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _i18n = __webpack_require__(1);

var _i18n2 = _interopRequireDefault(_i18n);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jquery2.default.entwine('ss.tree', function ($) {
	$('.cms-tree').entwine({
		fromDocument: {
			'oncontext_show.vakata': function oncontext_showVakata(e) {
				this.adjustContextClass();
			}
		},

		adjustContextClass: function adjustContextClass() {
			var menus = $('#vakata-contextmenu').find("ul ul");

			menus.each(function (i) {
				var col = "1",
				    count = $(menus[i]).find('li').length;

				if (count > 20) {
					col = "3";
				} else if (count > 10) {
					col = "2";
				}

				$(menus[i]).addClass('col-' + col).removeClass('right');

				$(menus[i]).find('li').on("mouseenter", function (e) {
					$(this).parent('ul').removeClass("right");
				});
			});
		},
		getTreeConfig: function getTreeConfig() {
			var self = this,
			    config = this._super(),
			    hints = this.getHints();
			config.plugins.push('contextmenu');
			config.contextmenu = {
				'items': function items(node) {

					var menuitems = {
						edit: {
							'label': node.hasClass('edit-disabled') ? _i18n2.default._t('CMS.EditPage', 'Edit page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree') : _i18n2.default._t('CMS.ViewPage', 'View page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
							'action': function action(obj) {
								$('.cms-container').entwine('.ss').loadPanel(_i18n2.default.sprintf(self.data('urlEditpage'), obj.data('id')));
							}
						}
					};

					if (!node.hasClass('nochildren')) {
						menuitems['showaslist'] = {
							'label': _i18n2.default._t('CMS.ShowAsList'),
							'action': function action(obj) {
								$('.cms-container').entwine('.ss').loadPanel(self.data('urlListview') + '&ParentID=' + obj.data('id'), null, { tabState: { 'pages-controller-cms-content': { 'tabSelector': '.content-listview' } } });
							}
						};
					}

					var pagetype = node.data('pagetype'),
					    id = node.data('id'),
					    allowedChildren = node.find('>a .item').data('allowedchildren'),
					    menuAllowedChildren = {},
					    hasAllowedChildren = false;

					$.each(allowedChildren, function (klass, title) {
						hasAllowedChildren = true;
						menuAllowedChildren["allowedchildren-" + klass] = {
							'label': '<span class="jstree-pageicon"></span>' + title,
							'_class': 'class-' + klass.replace(/[^a-zA-Z0-9\-_:.]+/g, '_'),
							'action': function action(obj) {
								$('.cms-container').entwine('.ss').loadPanel($.path.addSearchParams(_i18n2.default.sprintf(self.data('urlAddpage'), id, klass), self.data('extraParams')));
							}
						};
					});

					if (hasAllowedChildren) {
						menuitems['addsubpage'] = {
							'label': _i18n2.default._t('CMS.AddSubPage', 'Add page under this page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
							'submenu': menuAllowedChildren
						};
					}

					if (!node.hasClass('edit-disabled')) {
						menuitems['duplicate'] = {
							'label': _i18n2.default._t('CMS.Duplicate'),
							'submenu': [{
								'label': _i18n2.default._t('CMS.ThisPageOnly'),
								'action': function action(obj) {
									$('.cms-container').entwine('.ss').loadPanel($.path.addSearchParams(_i18n2.default.sprintf(self.data('urlDuplicate'), obj.data('id')), self.data('extraParams')));
								}
							}, {
								'label': _i18n2.default._t('CMS.ThisPageAndSubpages'),
								'action': function action(obj) {
									$('.cms-container').entwine('.ss').loadPanel($.path.addSearchParams(_i18n2.default.sprintf(self.data('urlDuplicatewithchildren'), obj.data('id')), self.data('extraParams')));
								}
							}]
						};
					}

					return menuitems;
				}
			};
			return config;
		}
	});

	$('.cms-tree a.jstree-clicked').entwine({
		onmatch: function onmatch() {
			var self = this,
			    panel = self.parents('.cms-panel-content'),
			    scrollTo;

			if (self.offset().top < 0 || self.offset().top > panel.height() - self.height()) {
				scrollTo = panel.scrollTop() + self.offset().top + panel.height() / 2;

				panel.animate({
					scrollTop: scrollTo
				}, 'slow');
			}
		}
	});

	$('.cms-tree-filtered .clear-filter').entwine({
		onclick: function onclick() {
			window.location = location.protocol + '//' + location.host + location.pathname;
		}
	});
});

/***/ }),
/* 16 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jquery2.default.entwine('ss', function ($) {

	var VIEW_TYPE_TREE = 'treeview';
	var VIEW_TYPE_LIST = 'listview';

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

	$('.cms-panel-deferred.cms-content-view').entwine({
		onadd: function onadd() {
			if (this.data('no-ajax')) {
				return;
			}
			var viewType = localStorage.getItem('ss.pages-view-type') || VIEW_TYPE_TREE;
			if (this.closest('.cms-content-tools').length > 0) {
				viewType = VIEW_TYPE_TREE;
			}
			var url = this.data('url-' + viewType);
			this.data('deferredNoCache', viewType === VIEW_TYPE_LIST);
			this.data('url', url + location.search);
			this._super();
		}
	});

	$('.cms .page-view-link').entwine({
		onclick: function onclick(e) {
			e.preventDefault();

			var viewType = $(this).data('view');
			var $contentView = this.closest('.cms-content-view');
			var url = $contentView.data('url-' + viewType);
			var isContentViewInSidebar = $contentView.closest('.cms-content-tools').length !== 0;

			localStorage.setItem('ss.pages-view-type', viewType);
			if (isContentViewInSidebar && viewType === VIEW_TYPE_LIST) {
				window.location = $contentView.data('url-listviewroot');
				return;
			}

			$contentView.data('url', url + location.search);
			$contentView.redraw();
		}
	});

	$('.cms .cms-clear-filter').entwine({
		onclick: function onclick(e) {
			e.preventDefault();
			window.location = $(this).prop('href');
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

/***/ }),
/* 17 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _i18n = __webpack_require__(1);

var _i18n2 = _interopRequireDefault(_i18n);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jquery2.default.entwine('ss', function ($) {
  $('#Form_VersionsForm').entwine({
    onmatch: function onmatch() {
      this._super();
    },
    onunmatch: function onunmatch() {
      this._super();
    },
    onsubmit: function onsubmit(e) {
      e.preventDefault();
      var id = this.find(':input[name=ID]').val();

      if (!id) {
        return false;
      }

      var url = null;
      var to = null;
      var from = null;

      var compare = this.find(':input[name=CompareMode]').is(':checked');
      var selected = this.find('table input[type=checkbox]').filter(':checked');

      if (compare) {
        if (selected.length !== 2) {
          return false;
        }

        to = selected.eq(0).val();
        from = selected.eq(1).val();
        url = _i18n2.default.sprintf(this.data('linkTmplCompare'), id, from, to);
      } else {
        to = selected.eq(0).val();
        url = _i18n2.default.sprintf(this.data('linkTmplShow'), id, to);
      }

      $('.cms-container').loadPanel(url, '', { pjax: 'CurrentForm' });
      return true;
    }
  });

  $('#Form_VersionsForm input[name=ShowUnpublished]').entwine({
    onmatch: function onmatch() {
      this.toggle();
      this._super();
    },
    onunmatch: function onunmatch() {
      this._super();
    },
    onchange: function onchange() {
      this.toggle();
    },
    toggle: function toggle() {
      var self = $(this);
      var unpublished = self.parents('form').find('tr[data-published=false]');

      if (self.attr('checked')) {
        unpublished.removeClass('ui-helper-hidden').show();
      } else {
        unpublished.addClass('ui-helper-hidden').hide()._unselect();
      }
    }
  });

  $('#Form_VersionsForm tbody tr').entwine({
    onclick: function onclick() {
      var compare = this.parents('form').find(':input[name=CompareMode]').attr('checked');
      var selected = this.siblings('.active');

      if (compare && this.hasClass('active')) {
        this._unselect();
        return;
      }

      if (compare) {
        if (selected.length > 1) {
          alert(_i18n2.default._t('CMS.ONLYSELECTTWO', 'You can only compare two versions at this time.'));
          return;
        }

        this._select();

        if (selected.length === 1) {
          this.parents('form').submit();
        }
        return;
      }
      this._select();
      selected._unselect();
      this.parents('form').submit();
    },
    _unselect: function _unselect() {
      this.get(0).classList.remove('active');
      this.find(':input[type=checkbox][checked]').attr('checked', false);
    },
    _select: function _select() {
      this.addClass('active');
      this.find(':input[type=checkbox]').attr('checked', true);
    }
  });
});

/***/ }),
/* 18 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jquery2.default.entwine('ss', function ($) {
	$('#Form_EditForm_RedirectionType input').entwine({
		onmatch: function onmatch() {
			var self = $(this);
			if (self.attr('checked')) this.toggle();
			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		},
		onclick: function onclick() {
			this.toggle();
		},
		toggle: function toggle() {
			if ($(this).attr('value') == 'Internal') {
				$('#Form_EditForm_ExternalURL_Holder').hide();
				$('#Form_EditForm_LinkToID_Holder').show();
			} else {
				$('#Form_EditForm_ExternalURL_Holder').show();
				$('#Form_EditForm_LinkToID_Holder').hide();
			}
		}
	});
});

/***/ }),
/* 19 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jquery2.default.entwine('ss', function ($) {
	$('.field.urlsegment:not(.readonly)').entwine({
		MaxPreviewLength: 55,

		Ellipsis: '...',

		onmatch: function onmatch() {
			if (this.find(':text').length) this.toggleEdit(false);
			this.redraw();

			this._super();
		},

		redraw: function redraw() {
			var field = this.find(':text'),
			    url = decodeURI(field.data('prefix') + field.val()),
			    previewUrl = url;

			if (url.length > this.getMaxPreviewLength()) {
				previewUrl = this.getEllipsis() + url.substr(url.length - this.getMaxPreviewLength(), url.length);
			}

			this.find('.URL-link').attr('href', encodeURI(url + field.data('suffix'))).text(previewUrl);
		},

		toggleEdit: function toggleEdit(toggle) {
			var field = this.find(':text');

			this.find('.preview-holder')[toggle ? 'hide' : 'show']();
			this.find('.edit-holder')[toggle ? 'show' : 'hide']();

			if (toggle) {
				field.data("origval", field.val());
				field.focus();
			}
		},

		update: function update() {
			var self = this,
			    field = this.find(':text'),
			    currentVal = field.data('origval'),
			    title = arguments[0],
			    updateVal = title && title !== "" ? title : field.val();

			if (currentVal != updateVal) {
				this.addClass('loading');
				this.suggest(updateVal, function (data) {
					field.val(decodeURIComponent(data.value));
					self.toggleEdit(false);
					self.removeClass('loading');
					self.redraw();
				});
			} else {
				this.toggleEdit(false);
				this.redraw();
			}
		},

		cancel: function cancel() {
			var field = this.find(':text');
			field.val(field.data("origval"));
			this.toggleEdit(false);
		},

		suggest: function suggest(val, callback) {
			var self = this,
			    field = self.find(':text'),
			    urlParts = $.path.parseUrl(self.closest('form').attr('action')),
			    url = urlParts.hrefNoSearch + '/field/' + field.attr('name') + '/suggest/?value=' + encodeURIComponent(val);
			if (urlParts.search) url += '&' + urlParts.search.replace(/^\?/, '');

			$.ajax({
				url: url,
				success: function success(data) {
					callback.apply(this, arguments);
				},
				error: function error(xhr, status) {
					xhr.statusText = xhr.responseText;
				},
				complete: function complete() {
					self.removeClass('loading');
				}
			});
		}
	});

	$('.field.urlsegment .edit').entwine({
		onclick: function onclick(e) {
			e.preventDefault();
			this.closest('.field').toggleEdit(true);
		}
	});

	$('.field.urlsegment .update').entwine({
		onclick: function onclick(e) {
			e.preventDefault();
			this.closest('.field').update();
		}
	});

	$('.field.urlsegment .cancel').entwine({
		onclick: function onclick(e) {
			e.preventDefault();
			this.closest('.field').cancel();
		}
	});
});

/***/ }),
/* 20 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(2);

var _Injector2 = _interopRequireDefault(_Injector);

var _AnchorSelectorField = __webpack_require__(23);

var _AnchorSelectorField2 = _interopRequireDefault(_AnchorSelectorField);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.default = function () {
  _Injector2.default.component.register('AnchorSelectorField', _AnchorSelectorField2.default);
};

/***/ }),
/* 21 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(2);

var _Injector2 = _interopRequireDefault(_Injector);

var _redux = __webpack_require__(11);

var _AnchorSelectorReducer = __webpack_require__(28);

var _AnchorSelectorReducer2 = _interopRequireDefault(_AnchorSelectorReducer);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.default = function () {
  _Injector2.default.reducer.register('cms', (0, _redux.combineReducers)({
    anchorSelector: _AnchorSelectorReducer2.default
  }));
};

/***/ }),
/* 22 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__(13);
__webpack_require__(14);
__webpack_require__(16);
__webpack_require__(15);
__webpack_require__(17);
__webpack_require__(18);
__webpack_require__(19);

__webpack_require__(12);

/***/ }),
/* 23 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ConnectedAnchorSelectorField = exports.Component = undefined;

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _i18n = __webpack_require__(1);

var _i18n2 = _interopRequireDefault(_i18n);

var _react = __webpack_require__(3);

var _react2 = _interopRequireDefault(_react);

var _isomorphicFetch = __webpack_require__(31);

var _isomorphicFetch2 = _interopRequireDefault(_isomorphicFetch);

var _reactRedux = __webpack_require__(32);

var _redux = __webpack_require__(11);

var _reduxForm = __webpack_require__(34);

var _SilverStripeComponent = __webpack_require__(35);

var _SilverStripeComponent2 = _interopRequireDefault(_SilverStripeComponent);

var _AnchorSelectorActions = __webpack_require__(27);

var anchorSelectorActions = _interopRequireWildcard(_AnchorSelectorActions);

var _AnchorSelectorStates = __webpack_require__(10);

var _AnchorSelectorStates2 = _interopRequireDefault(_AnchorSelectorStates);

var _FieldHolder = __webpack_require__(30);

var _FieldHolder2 = _interopRequireDefault(_FieldHolder);

var _reactSelect = __webpack_require__(33);

var _getFormState = __webpack_require__(37);

var _getFormState2 = _interopRequireDefault(_getFormState);

var _classnames = __webpack_require__(36);

var _classnames2 = _interopRequireDefault(_classnames);

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var noop = function noop() {
  return null;
};

var AnchorSelectorField = function (_SilverStripeComponen) {
  _inherits(AnchorSelectorField, _SilverStripeComponen);

  function AnchorSelectorField(props) {
    _classCallCheck(this, AnchorSelectorField);

    var _this = _possibleConstructorReturn(this, (AnchorSelectorField.__proto__ || Object.getPrototypeOf(AnchorSelectorField)).call(this, props));

    _this.handleChange = _this.handleChange.bind(_this);
    _this.handleLoadingError = _this.handleLoadingError.bind(_this);
    return _this;
  }

  _createClass(AnchorSelectorField, [{
    key: 'componentDidMount',
    value: function componentDidMount() {
      this.ensurePagesLoaded();
    }
  }, {
    key: 'componentWillReceiveProps',
    value: function componentWillReceiveProps(nextProps) {
      if (this.props.pageId !== nextProps.pageId) {
        this.ensurePagesLoaded(nextProps);
      }
    }
  }, {
    key: 'ensurePagesLoaded',
    value: function ensurePagesLoaded() {
      var _this2 = this;

      var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.props;

      if (props.loadingState !== _AnchorSelectorStates2.default.DIRTY || !props.pageId) {
        return Promise.resolve();
      }

      props.actions.anchorSelector.beginUpdating(props.pageId);

      var fetchURL = props.data.endpoint.replace(/:id/, props.pageId);
      return (0, _isomorphicFetch2.default)(fetchURL, { credentials: 'same-origin' }).then(function (response) {
        return response.json();
      }).then(function (anchors) {
        props.actions.anchorSelector.updated(props.pageId, anchors);
        return anchors;
      }).catch(function (error) {
        props.actions.anchorSelector.updateFailed(props.pageId);
        _this2.handleLoadingError(error, props);
      });
    }
  }, {
    key: 'getDropdownOptions',
    value: function getDropdownOptions() {
      var _this3 = this;

      var options = this.props.anchors.map(function (value) {
        return { value: value };
      });

      if (this.props.value && !this.props.anchors.find(function (value) {
        return value === _this3.props.value;
      })) {
        options.unshift({ value: this.props.value });
      }
      return options;
    }
  }, {
    key: 'handleChange',
    value: function handleChange(value) {
      if (typeof this.props.onChange === 'function') {
        this.props.onChange(value ? value.value : '');
      }
    }
  }, {
    key: 'handleLoadingError',
    value: function handleLoadingError(error) {
      var props = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.props;

      if (props.onLoadingError === noop) {
        throw error;
      }

      return props.onLoadingError({
        errors: [{
          value: error.message,
          type: 'error'
        }]
      });
    }
  }, {
    key: 'render',
    value: function render() {
      var inputProps = {
        id: this.props.id
      };
      var className = (0, _classnames2.default)('anchorselectorfield', this.props.extraClass);
      var options = this.getDropdownOptions();
      var value = this.props.value || '';
      var placeholder = _i18n2.default._t('CMS.ANCHOR_SELECT_OR_TYPE', 'Select or enter anchor');
      return _react2.default.createElement(_reactSelect.Creatable, {
        searchable: true,
        options: options,
        className: className,
        name: this.props.name,
        inputProps: inputProps,
        onChange: this.handleChange,
        onBlurResetsInput: true,
        value: value,
        placeholder: placeholder,
        labelKey: 'value'
      });
    }
  }]);

  return AnchorSelectorField;
}(_SilverStripeComponent2.default);

AnchorSelectorField.propTypes = {
  extraClass: _react2.default.PropTypes.string,
  id: _react2.default.PropTypes.string,
  name: _react2.default.PropTypes.string.isRequired,
  onChange: _react2.default.PropTypes.func,
  value: _react2.default.PropTypes.string,
  attributes: _react2.default.PropTypes.oneOfType([_react2.default.PropTypes.object, _react2.default.PropTypes.array]),
  pageId: _react2.default.PropTypes.number,
  anchors: _react2.default.PropTypes.array,
  loadingState: _react2.default.PropTypes.oneOf(Object.keys(_AnchorSelectorStates2.default).map(function (key) {
    return _AnchorSelectorStates2.default[key];
  })),
  onLoadingError: _react2.default.PropTypes.func,
  data: _react2.default.PropTypes.shape({
    endpoint: _react2.default.PropTypes.string,
    targetFieldName: _react2.default.PropTypes.string
  })
};

AnchorSelectorField.defaultProps = {
  value: '',
  extraClass: '',
  onLoadingError: noop,
  attributes: {}
};

function mapStateToProps(state, ownProps) {
  var selector = (0, _reduxForm.formValueSelector)(ownProps.formid, _getFormState2.default);
  var targetFieldName = ownProps && ownProps.data && ownProps.data.targetFieldName || 'PageID';
  var pageId = Number(selector(state, targetFieldName) || 0);

  var anchors = [];
  var page = pageId ? state.cms.anchorSelector.pages.find(function (next) {
    return next.id === pageId;
  }) : null;
  if (page && page.loadingState === _AnchorSelectorStates2.default.SUCCESS) {
    anchors = page.anchors;
  }

  var loadingState = null;
  if (page) {
    loadingState = page.loadingState;
  } else if (pageId) {
    loadingState = _AnchorSelectorStates2.default.DIRTY;
  } else {
    loadingState = _AnchorSelectorStates2.default.SUCCESS;
  }

  return { pageId: pageId, anchors: anchors, loadingState: loadingState };
}

function mapDispatchToProps(dispatch) {
  return {
    actions: {
      anchorSelector: (0, _redux.bindActionCreators)(anchorSelectorActions, dispatch)
    }
  };
}

var ConnectedAnchorSelectorField = (0, _reactRedux.connect)(mapStateToProps, mapDispatchToProps)(AnchorSelectorField);

exports.Component = AnchorSelectorField;
exports.ConnectedAnchorSelectorField = ConnectedAnchorSelectorField;
exports.default = (0, _FieldHolder2.default)(ConnectedAnchorSelectorField);

/***/ }),
/* 24 */,
/* 25 */,
/* 26 */,
/* 27 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.beginUpdating = beginUpdating;
exports.updated = updated;
exports.updateFailed = updateFailed;

var _AnchorSelectorActionTypes = __webpack_require__(9);

var _AnchorSelectorActionTypes2 = _interopRequireDefault(_AnchorSelectorActionTypes);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function beginUpdating(pageId) {
  return {
    type: _AnchorSelectorActionTypes2.default.ANCHORSELECTOR_UPDATING,
    payload: { pageId: pageId }
  };
}

function updated(pageId, anchors) {
  return {
    type: _AnchorSelectorActionTypes2.default.ANCHORSELECTOR_UPDATED,
    payload: { pageId: pageId, anchors: anchors }
  };
}

function updateFailed(pageId) {
  return {
    type: _AnchorSelectorActionTypes2.default.ANCHORSELECTOR_UPDATE_FAILED,
    payload: { pageId: pageId }
  };
}

/***/ }),
/* 28 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = anchorSelectorReducer;

var _deepFreezeStrict = __webpack_require__(29);

var _deepFreezeStrict2 = _interopRequireDefault(_deepFreezeStrict);

var _AnchorSelectorActionTypes = __webpack_require__(9);

var _AnchorSelectorActionTypes2 = _interopRequireDefault(_AnchorSelectorActionTypes);

var _AnchorSelectorStates = __webpack_require__(10);

var _AnchorSelectorStates2 = _interopRequireDefault(_AnchorSelectorStates);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var initialState = (0, _deepFreezeStrict2.default)({ pages: [] });

function anchorSelectorReducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : initialState;
  var action = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

  var updatePage = function updatePage(loadingState, anchors) {
    var id = action.payload.pageId;
    return (0, _deepFreezeStrict2.default)({
      pages: [].concat(_toConsumableArray(state.pages.filter(function (next) {
        return next.id !== id;
      })), [{
        id: id,
        loadingState: loadingState,
        anchors: anchors
      }]).sort(function (left, right) {
        return left.id - right.id;
      })
    });
  };

  switch (action.type) {
    case _AnchorSelectorActionTypes2.default.ANCHORSELECTOR_UPDATING:
      {
        return updatePage(_AnchorSelectorStates2.default.UPDATING, []);
      }

    case _AnchorSelectorActionTypes2.default.ANCHORSELECTOR_UPDATED:
      {
        return updatePage(_AnchorSelectorStates2.default.SUCCESS, action.payload.anchors);
      }

    case _AnchorSelectorActionTypes2.default.ANCHORSELECTOR_UPDATE_FAILED:
      {
        return updatePage(_AnchorSelectorStates2.default.FAILED, []);
      }

    default:
      return state;
  }
}

/***/ }),
/* 29 */
/***/ (function(module, exports) {

module.exports = DeepFreezeStrict;

/***/ }),
/* 30 */
/***/ (function(module, exports) {

module.exports = FieldHolder;

/***/ }),
/* 31 */
/***/ (function(module, exports) {

module.exports = IsomorphicFetch;

/***/ }),
/* 32 */
/***/ (function(module, exports) {

module.exports = ReactRedux;

/***/ }),
/* 33 */
/***/ (function(module, exports) {

module.exports = ReactSelect;

/***/ }),
/* 34 */
/***/ (function(module, exports) {

module.exports = ReduxForm;

/***/ }),
/* 35 */
/***/ (function(module, exports) {

module.exports = SilverStripeComponent;

/***/ }),
/* 36 */
/***/ (function(module, exports) {

module.exports = classnames;

/***/ }),
/* 37 */
/***/ (function(module, exports) {

module.exports = getFormState;

/***/ })
/******/ ]);
//# sourceMappingURL=bundle.js.map