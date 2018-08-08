<<<<<<< HEAD
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
/******/ 	return __webpack_require__(__webpack_require__.s = 24);
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

module.exports = ReactApollo;

/***/ }),
/* 4 */
/***/ (function(module, exports) {

module.exports = React;

/***/ }),
/* 5 */
/***/ (function(module, exports) {

module.exports = ReactRedux;

/***/ }),
/* 6 */,
/* 7 */,
/* 8 */,
/* 9 */,
/* 10 */
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
/* 11 */
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
/* 12 */
/***/ (function(module, exports) {

module.exports = GraphQLTag;

/***/ }),
/* 13 */
/***/ (function(module, exports) {

module.exports = Redux;

/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _registerReducers = __webpack_require__(23);

var _registerReducers2 = _interopRequireDefault(_registerReducers);

var _registerComponents = __webpack_require__(22);

var _registerComponents2 = _interopRequireDefault(_registerComponents);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

window.document.addEventListener('DOMContentLoaded', function () {
  (0, _registerComponents2.default)();
  (0, _registerReducers2.default)();
});

/***/ }),
/* 15 */
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
/* 16 */
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
			if (this.is(':disabled')) {
				e.preventDefault();
				return false;
			}

			var version = this.parents('form:first').find(':input[name=Version]').val();

			var message = version ? _i18n2.default.sprintf(_i18n2.default._t('CMS.RollbackToVersion', 'Do you really want to roll back to version #%s of this page?'), version) : _i18n2.default._t('CMS.ConfirmRestoreFromLive', 'Are you sure you want to revert draft to when the page was last published?');

			if (!confirm(message)) {
				e.preventDefault();
				return false;
			}

			return this._super(e);
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
/* 17 */
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

		showListViewFor: function showListViewFor(id) {
			var $contentView = this.closest('.cms-content-view');
			var isContentViewInSidebar = $contentView.closest('.cms-content-tools').length !== 0;

			if (isContentViewInSidebar) {
				window.location = $contentView.data('url-listviewroot');
				return;
			}
			var url = $contentView.data('url-listview') + location.search;
			var urlWithParams = $.path.addSearchParams(url, {
				ParentID: id
			});
			$contentView.data('url', urlWithParams);
			$contentView.entwine('.ss').redraw();
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
								self.showListViewFor(obj.data('id'));
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

	$('.cms-tree .subtree-list-link').entwine({
		onclick: function onclick(e) {
			e.preventDefault();
			this.closest('.cms-tree').showListViewFor(this.data('id'));
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
/* 19 */
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
/* 20 */
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
/* 21 */
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
/* 22 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(2);

var _Injector2 = _interopRequireDefault(_Injector);

var _AnchorSelectorField = __webpack_require__(25);

var _AnchorSelectorField2 = _interopRequireDefault(_AnchorSelectorField);

var _readOnePageQuery = __webpack_require__(31);

var _readOnePageQuery2 = _interopRequireDefault(_readOnePageQuery);

var _revertToPageVersionMutation = __webpack_require__(32);

var _revertToPageVersionMutation2 = _interopRequireDefault(_revertToPageVersionMutation);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.default = function () {
  _Injector2.default.component.register('AnchorSelectorField', _AnchorSelectorField2.default);

  _Injector2.default.transform('pages-history', function (updater) {
    updater.component('HistoryViewer.pages-controller-cms-content', _readOnePageQuery2.default, 'PageHistoryViewer');
  });

  _Injector2.default.transform('pages-history-revert', function (updater) {
    updater.component('HistoryViewerToolbar.VersionedAdmin.HistoryViewer.SiteTree.HistoryViewerVersionDetail', _revertToPageVersionMutation2.default, 'PageRevertMutation');
  });
};

/***/ }),
/* 23 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(2);

var _Injector2 = _interopRequireDefault(_Injector);

var _redux = __webpack_require__(13);

var _AnchorSelectorReducer = __webpack_require__(30);

var _AnchorSelectorReducer2 = _interopRequireDefault(_AnchorSelectorReducer);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.default = function () {
  _Injector2.default.reducer.register('cms', (0, _redux.combineReducers)({
    anchorSelector: _AnchorSelectorReducer2.default
  }));
};

/***/ }),
/* 24 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__(15);
__webpack_require__(16);
__webpack_require__(18);
__webpack_require__(17);
__webpack_require__(19);
__webpack_require__(20);
__webpack_require__(21);

__webpack_require__(14);

/***/ }),
/* 25 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ConnectedAnchorSelectorField = exports.Component = undefined;

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _i18n = __webpack_require__(1);

var _i18n2 = _interopRequireDefault(_i18n);

var _react = __webpack_require__(4);

var _react2 = _interopRequireDefault(_react);

var _isomorphicFetch = __webpack_require__(35);

var _isomorphicFetch2 = _interopRequireDefault(_isomorphicFetch);

var _reactRedux = __webpack_require__(5);

var _redux = __webpack_require__(13);

var _reduxForm = __webpack_require__(37);

var _SilverStripeComponent = __webpack_require__(38);

var _SilverStripeComponent2 = _interopRequireDefault(_SilverStripeComponent);

var _AnchorSelectorActions = __webpack_require__(29);

var anchorSelectorActions = _interopRequireWildcard(_AnchorSelectorActions);

var _AnchorSelectorStates = __webpack_require__(11);

var _AnchorSelectorStates2 = _interopRequireDefault(_AnchorSelectorStates);

var _FieldHolder = __webpack_require__(34);

var _FieldHolder2 = _interopRequireDefault(_FieldHolder);

var _reactSelect = __webpack_require__(36);

var _getFormState = __webpack_require__(40);

var _getFormState2 = _interopRequireDefault(_getFormState);

var _classnames = __webpack_require__(39);

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
/* 26 */,
/* 27 */,
/* 28 */,
/* 29 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.beginUpdating = beginUpdating;
exports.updated = updated;
exports.updateFailed = updateFailed;

var _AnchorSelectorActionTypes = __webpack_require__(10);

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
/* 30 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = anchorSelectorReducer;

var _deepFreezeStrict = __webpack_require__(33);

var _deepFreezeStrict2 = _interopRequireDefault(_deepFreezeStrict);

var _AnchorSelectorActionTypes = __webpack_require__(10);

var _AnchorSelectorActionTypes2 = _interopRequireDefault(_AnchorSelectorActionTypes);

var _AnchorSelectorStates = __webpack_require__(11);

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
/* 31 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.config = exports.query = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _templateObject = _taggedTemplateLiteral(['\nquery ReadHistoryViewerPage ($page_id: ID!, $limit: Int!, $offset: Int!) {\n  readOnePage(\n    Versioning: {\n      Mode: LATEST\n    },\n    ID: $page_id\n  ) {\n    ID\n    Versions (limit: $limit, offset: $offset) {\n      pageInfo {\n        totalCount\n      }\n      edges {\n        node {\n          Version\n          AbsoluteLink\n          Author {\n            FirstName\n            Surname\n          }\n          Publisher {\n            FirstName\n            Surname\n          }\n          Published\n          LiveVersion\n          LatestDraftVersion\n          LastEdited\n        }\n      }\n    }\n  }\n}\n'], ['\nquery ReadHistoryViewerPage ($page_id: ID!, $limit: Int!, $offset: Int!) {\n  readOnePage(\n    Versioning: {\n      Mode: LATEST\n    },\n    ID: $page_id\n  ) {\n    ID\n    Versions (limit: $limit, offset: $offset) {\n      pageInfo {\n        totalCount\n      }\n      edges {\n        node {\n          Version\n          AbsoluteLink\n          Author {\n            FirstName\n            Surname\n          }\n          Publisher {\n            FirstName\n            Surname\n          }\n          Published\n          LiveVersion\n          LatestDraftVersion\n          LastEdited\n        }\n      }\n    }\n  }\n}\n']);

var _reactApollo = __webpack_require__(3);

var _graphqlTag = __webpack_require__(12);

var _graphqlTag2 = _interopRequireDefault(_graphqlTag);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _taggedTemplateLiteral(strings, raw) { return Object.freeze(Object.defineProperties(strings, { raw: { value: Object.freeze(raw) } })); }

var query = (0, _graphqlTag2.default)(_templateObject);

var config = {
  options: function options(_ref) {
    var recordId = _ref.recordId,
        limit = _ref.limit,
        page = _ref.page;

    return {
      variables: {
        limit: limit,
        offset: ((page || 1) - 1) * limit,
        page_id: recordId
      }
    };
  },
  props: function props(_ref2) {
    var _ref2$data = _ref2.data,
        error = _ref2$data.error,
        refetch = _ref2$data.refetch,
        readOnePage = _ref2$data.readOnePage,
        networkLoading = _ref2$data.loading,
        _ref2$ownProps = _ref2.ownProps,
        _ref2$ownProps$action = _ref2$ownProps.actions,
        actions = _ref2$ownProps$action === undefined ? {
      versions: {}
    } : _ref2$ownProps$action,
        limit = _ref2$ownProps.limit,
        recordId = _ref2$ownProps.recordId;

    var versions = readOnePage || null;

    var errors = error && error.graphQLErrors && error.graphQLErrors.map(function (graphQLError) {
      return graphQLError.message;
    });

    return {
      loading: networkLoading || !versions,
      versions: versions,
      graphQLErrors: errors,
      actions: _extends({}, actions, {
        versions: _extends({}, versions, {
          goToPage: function goToPage(page) {
            refetch({
              offset: ((page || 1) - 1) * limit,
              limit: limit,
              page_id: recordId
            });
          }
        })
      })
    };
  }
};

exports.query = query;
exports.config = config;
exports.default = (0, _reactApollo.graphql)(query, config);

/***/ }),
/* 32 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.config = exports.mutation = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _templateObject = _taggedTemplateLiteral(['\nmutation revertPageToVersion($id:ID!, $fromStage:VersionedStage!, $toStage:VersionedStage!, $fromVersion:Int!) {\n  copySilverStripeSiteTreeToStage(Input: {\n    ID: $id\n    FromVersion: $fromVersion\n    FromStage: $fromStage\n    ToStage: $toStage\n  }) {\n    ID\n  }\n}\n\n'], ['\nmutation revertPageToVersion($id:ID!, $fromStage:VersionedStage!, $toStage:VersionedStage!, $fromVersion:Int!) {\n  copySilverStripeSiteTreeToStage(Input: {\n    ID: $id\n    FromVersion: $fromVersion\n    FromStage: $fromStage\n    ToStage: $toStage\n  }) {\n    ID\n  }\n}\n\n']);

var _reactApollo = __webpack_require__(3);

var _graphqlTag = __webpack_require__(12);

var _graphqlTag2 = _interopRequireDefault(_graphqlTag);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _taggedTemplateLiteral(strings, raw) { return Object.freeze(Object.defineProperties(strings, { raw: { value: Object.freeze(raw) } })); }

var mutation = (0, _graphqlTag2.default)(_templateObject);

var config = {
  props: function props(_ref) {
    var mutate = _ref.mutate,
        actions = _ref.ownProps.actions;

    var revertToVersion = function revertToVersion(id, fromVersion, fromStage, toStage) {
      return mutate({
        variables: {
          id: id,
          fromVersion: fromVersion,
          fromStage: fromStage,
          toStage: toStage
        }
      });
    };

    return {
      actions: _extends({}, actions, {
        revertToVersion: revertToVersion
      })
    };
  },
  options: {
    refetchQueries: ['ReadHistoryViewerPage']
  }
};

exports.mutation = mutation;
exports.config = config;
exports.default = (0, _reactApollo.graphql)(mutation, config);

/***/ }),
/* 33 */
/***/ (function(module, exports) {

module.exports = DeepFreezeStrict;

/***/ }),
/* 34 */
/***/ (function(module, exports) {

module.exports = FieldHolder;

/***/ }),
/* 35 */
/***/ (function(module, exports) {

module.exports = IsomorphicFetch;

/***/ }),
/* 36 */
/***/ (function(module, exports) {

module.exports = ReactSelect;

/***/ }),
/* 37 */
/***/ (function(module, exports) {

module.exports = ReduxForm;

/***/ }),
/* 38 */
/***/ (function(module, exports) {

module.exports = SilverStripeComponent;

/***/ }),
/* 39 */
/***/ (function(module, exports) {

module.exports = classnames;

/***/ }),
/* 40 */
/***/ (function(module, exports) {

module.exports = getFormState;

/***/ })
/******/ ]);
//# sourceMappingURL=bundle.js.map
=======
!function(e){function t(a){if(n[a])return n[a].exports;var i=n[a]={i:a,l:!1,exports:{}};return e[a].call(i.exports,i,i.exports,t),i.l=!0,i.exports}var n={};t.m=e,t.c=n,t.i=function(e){return e},t.d=function(e,n,a){t.o(e,n)||Object.defineProperty(e,n,{configurable:!1,enumerable:!0,get:a})},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=24)}([function(e,t){e.exports=jQuery},function(e,t){e.exports=i18n},function(e,t){e.exports=Injector},function(e,t){e.exports=ReactApollo},function(e,t){e.exports=React},function(e,t){e.exports=ReactRedux},,,,,function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default={ANCHORSELECTOR_UPDATED:"ANCHORSELECTOR_UPDATED",ANCHORSELECTOR_UPDATING:"ANCHORSELECTOR_UPDATING",ANCHORSELECTOR_UPDATE_FAILED:"ANCHORSELECTOR_UPDATE_FAILED"}},function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default={SUCCESS:"SUCCESS",DIRTY:"DIRTY",UPDATING:"UPDATING",FAILED:"FAILED"}},function(e,t){e.exports=GraphQLTag},function(e,t){e.exports=Redux},function(e,t,n){"use strict";function a(e){return e&&e.__esModule?e:{default:e}}var i=n(23),r=a(i),o=n(22),s=a(o);window.document.addEventListener("DOMContentLoaded",function(){(0,s.default)(),(0,r.default)()})},function(e,t,n){"use strict";var a=n(0);(function(e){return e&&e.__esModule?e:{default:e}})(a).default.entwine("ss",function(e){e(".TreeDropdownField").entwine({OldValue:null}),e("#Form_AddForm_ParentID_Holder .treedropdownfield").entwine({onmatch:function(){this._super(),e(".cms-add-form").updateTypeList()}}),e(".cms-add-form .parent-mode :input").entwine({onclick:function(e){var t=this.closest("form").find("#Form_AddForm_ParentID_Holder .TreeDropdownField");"top"==this.val()?(t.setOldValue(t.getValue()),t.setValue(0)):(t.setValue(t.getOldValue()||0),t.setOldValue(null)),t.refresh(),t.trigger("change")}}),e(".cms-add-form").entwine({ParentCache:{},onadd:function(){var t=this;this.find("#Form_AddForm_ParentID_Holder .TreeDropdownField").bind("change",function(){t.updateTypeList()}),this.find(".SelectionGroup.parent-mode").bind("change",function(){t.updateTypeList()}),"top"==e(".cms-add-form .parent-mode :input").val()&&this.updateTypeList()},loadCachedChildren:function(e){var t=this.getParentCache();return void 0!==t[e]?t[e]:null},saveCachedChildren:function(e,t){var n=this.getParentCache();n[e]=t,this.setParentCache(n)},updateTypeList:function(){var t=this.data("hints"),n=this.find("#Form_AddForm_ParentID"),a=this.find("input[name=ParentModeField]:checked").val(),i=n.data("metadata"),r="child"===a?n.getValue():null,o=i?i.ClassName:null,s=o&&"child"===a&&r?o:"Root",d=void 0!==t[s]?t[s]:null,l=this,u=d&&void 0!==d.defaultChild?d.defaultChild:null,c=[];if(r){if(this.hasClass("loading"))return;return this.addClass("loading"),null!==(c=this.loadCachedChildren(r))?(this.updateSelectionFilter(c,u),void this.removeClass("loading")):(e.ajax({url:l.data("childfilter"),data:{ParentID:r},success:function(e){l.saveCachedChildren(r,e),l.updateSelectionFilter(e,u)},complete:function(){l.removeClass("loading")}}),!1)}c=d&&void 0!==d.disallowedChildren?d.disallowedChildren:[],this.updateSelectionFilter(c,u)},updateSelectionFilter:function(t,n){var a=null;if(this.find("#Form_AddForm_PageType div.radio").each(function(){var n=e(this).find("input").val(),i=-1===e.inArray(n,t);e(this).setEnabled(i),i||e(this).setSelected(!1),a=null===a?i:a&&i}),n)var i=this.find("#Form_AddForm_PageType div.radio input[value="+n+"]").parents("li:first");else var i=this.find("#Form_AddForm_PageType div.radio:not(.disabled):first");i.setSelected(!0),i.siblings().setSelected(!1),this.find("#Form_AddForm_PageType div.radio:not(.disabled)").length?this.find("button[name=action_doAdd]").removeAttr("disabled"):this.find("button[name=action_doAdd]").attr("disabled","disabled"),this.find(".message-restricted")[a?"hide":"show"]()}}),e(".cms-add-form #Form_AddForm_PageType div.radio").entwine({onclick:function(e){this.setSelected(!0)},setSelected:function(e){var t=this.find("input");e&&!t.is(":disabled")?(this.siblings().setSelected(!1),this.toggleClass("selected",!0),t.prop("checked",!0)):(this.toggleClass("selected",!1),t.prop("checked",!1))},setEnabled:function(t){e(this).toggleClass("disabled",!t),t?e(this).find("input").removeAttr("disabled"):e(this).find("input").attr("disabled","disabled").removeAttr("checked")}}),e(".cms-content-addpage-button").entwine({onclick:function(t){var n,a=e(".cms-tree"),i=e(".cms-list"),r=0;if(a.is(":visible")){var o=a.jstree("get_selected");r=o?e(o[0]).data("id"):null}else{var s=i.find('input[name="Page[GridState]"]').val();s&&(r=parseInt(JSON.parse(s).ParentID,10))}var d,l={selector:this.data("targetPanel"),pjax:this.data("pjax")};r?(n=this.data("extraParams")?this.data("extraParams"):"",d=e.path.addSearchParams(i18n.sprintf(this.data("urlAddpage"),r),n)):d=this.attr("href"),e(".cms-container").loadPanel(d,null,l),t.preventDefault(),this.blur()}})})},function(e,t,n){"use strict";function a(e){return e&&e.__esModule?e:{default:e}}var i=n(0),r=a(i),o=n(1),s=a(o);r.default.entwine("ss",function(e){e(".cms-edit-form :input[name=ClassName]").entwine({onchange:function(){alert(s.default._t("CMS.ALERTCLASSNAME"))}}),e(".cms-edit-form input[name=Title]").entwine({onmatch:function(){var t=this;t.data("OrigVal",t.val());var n=t.closest("form"),a=e("input:text[name=URLSegment]",n),i=e("input[name=LiveLink]",n);a.length>0&&(t._addActions(),this.bind("change",function(n){var r=t.data("OrigVal"),o=t.val();t.data("OrigVal",o),0===a.val().indexOf(a.data("defaultUrl"))&&""==i.val()?t.updateURLSegment(o):e(".update",t.parent()).show(),t.updateRelatedFields(o,r),t.updateBreadcrumbLabel(o)})),this._super()},onunmatch:function(){this._super()},updateRelatedFields:function(t,n){this.parents("form").find("input[name=MetaTitle], input[name=MenuTitle]").each(function(){var a=e(this);a.val()==n&&(a.val(t),a.updatedRelatedFields&&a.updatedRelatedFields())})},updateURLSegment:function(t){var n=e("input:text[name=URLSegment]",this.closest("form")),a=n.closest(".field.urlsegment"),i=e(".update",this.parent());a.update(t),i.is(":visible")&&i.hide()},updateBreadcrumbLabel:function(t){var n=(e(".cms-edit-form input[name=ID]").val(),e("span.cms-panel-link.crumb"));t&&""!=t&&n.text(t)},_addActions:function(){var t,n=this;t=e("<button />",{class:"update btn btn-outline-secondary form__field-update-url",text:s.default._t("CMS.UpdateURL"),type:"button",click:function(e){e.preventDefault(),n.updateURLSegment(n.val())}}),t.insertAfter(n),t.parent(".form__field-holder").addClass("input-group"),t.hide()}}),e(".cms-edit-form .parentTypeSelector").entwine({onmatch:function(){var e=this;this.find(":input[name=ParentType]").bind("click",function(t){e._toggleSelection(t)}),this.find(".TreeDropdownField").bind("change",function(t){e._changeParentId(t)}),this._changeParentId(),this._toggleSelection(),this._super()},onunmatch:function(){this._super()},_toggleSelection:function(t){var n=this.find(":input[name=ParentType]:checked").val(),a=this.find("#Form_EditForm_ParentID_Holder");"root"==n?this.find(":input[name=ParentID]").val(0):this.find(":input[name=ParentID]").val(this.find("#Form_EditForm_ParentType_subpage").data("parentIdValue")),"root"!=n?a.slideDown(400,function(){e(this).css("overflow","visible")}):a.slideUp()},_changeParentId:function(e){var t=this.find(":input[name=ParentID]").val();this.find("#Form_EditForm_ParentType_subpage").data("parentIdValue",t)}}),e(".cms-edit-form .btn-toolbar #Form_EditForm_action_doRollback, .cms-edit-form .btn-toolbar #Form_EditForm_action_rollback").entwine({onclick:function(e){if(this.is(":disabled"))return e.preventDefault(),!1;var t=this.parents("form:first").find(":input[name=Version]").val(),n=t?s.default.sprintf(s.default._t("CMS.RollbackToVersion","Do you really want to roll back to version #%s of this page?"),t):s.default._t("CMS.ConfirmRestoreFromLive","Are you sure you want to revert draft to when the page was last published?");return confirm(n)?this._super(e):(e.preventDefault(),!1)}}),e(".cms-edit-form .btn-toolbar #Form_EditForm_action_archive").entwine({onclick:function(e){var t=this.parents("form:first"),n="";return n=t.find("input[name=ArchiveWarningMessage]").val().replace(/\\n/g,"\n"),!!confirm(n)&&this._super(e)}}),e(".cms-edit-form .btn-toolbar #Form_EditForm_action_restore").entwine({onclick:function(e){var t=this.parents("form:first"),n=t.find(":input[name=Version]").val(),a="",i=this.data("toRoot");return a=s.default.sprintf(s.default._t(i?"CMS.RestoreToRoot":"CMS.Restore"),n),!!confirm(a)&&this._super(e)}}),e(".cms-edit-form .btn-toolbar #Form_EditForm_action_unpublish").entwine({onclick:function(e){var t=this.parents("form:first"),n=t.find(":input[name=Version]").val(),a="";return a=s.default.sprintf(s.default._t("CMS.Unpublish"),n),!!confirm(a)&&this._super(e)}}),e(".cms-edit-form.changed").entwine({onmatch:function(t){this.find("button[data-text-alternate]").each(function(){var t=e(this),n=t.find(".btn__title"),a=t.data("textAlternate");a&&(t.data("textStandard",n.text()),n.text(a));var i=t.data("btnAlternate");i&&(t.data("btnStandard",t.attr("class")),t.attr("class",i),t.removeClass("btn-outline-secondary").addClass("btn-primary"));var r=t.data("btnAlternateAdd");r&&t.addClass(r);var o=t.data("btnAlternateRemove");o&&t.removeClass(o)}),this._super(t)},onunmatch:function(t){this.find("button[data-text-alternate]").each(function(){var t=e(this),n=t.find(".btn__title"),a=t.data("textStandard");a&&n.text(a);var i=t.data("btnStandard");i&&(t.attr("class",i),t.addClass("btn-outline-secondary").removeClass("btn-primary"));var r=t.data("btnAlternateAdd");r&&t.removeClass(r);var o=t.data("btnAlternateRemove");o&&t.addClass(o)}),this._super(t)}}),e(".cms-edit-form .btn-toolbar button[name=action_publish]").entwine({onbuttonafterrefreshalternate:function(){this.data("showingAlternate")?(this.addClass("btn-primary"),this.removeClass("btn-secondary")):(this.removeClass("btn-primary"),this.addClass("btn-secondary"))}}),e(".cms-edit-form .btn-toolbar button[name=action_save]").entwine({onbuttonafterrefreshalternate:function(){this.data("showingAlternate")?(this.addClass("btn-primary"),this.removeClass("btn-secondary")):(this.removeClass("btn-primary"),this.addClass("btn-secondary"))}}),e('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').entwine({onmatch:function(){this.redraw(),this._super()},onunmatch:function(){this._super()},redraw:function(){var t=e(".cms-edit-form.CMSPageSettingsController #Form_EditForm_ParentID_Holder");"Form_EditForm_ParentType_root"==e(this).attr("id")?t.slideUp():t.slideDown()},onclick:function(){this.redraw()}}),"Form_EditForm_ParentType_root"==e('.cms-edit-form.CMSPageSettingsController input[name="ParentType"]:checked').attr("id")&&e(".cms-edit-form.CMSPageSettingsController #Form_EditForm_ParentID_Holder").hide()})},function(e,t,n){"use strict";function a(e){return e&&e.__esModule?e:{default:e}}var i=n(0),r=a(i),o=n(1),s=a(o);r.default.entwine("ss.tree",function(e){e(".cms-tree").entwine({fromDocument:{"oncontext_show.vakata":function(e){this.adjustContextClass()}},adjustContextClass:function(){var t=e("#vakata-contextmenu").find("ul ul");t.each(function(n){var a="1",i=e(t[n]).find("li").length;i>20?a="3":i>10&&(a="2"),e(t[n]).addClass("col-"+a).removeClass("right"),e(t[n]).find("li").on("mouseenter",function(t){e(this).parent("ul").removeClass("right")})})},showListViewFor:function(t){var n=this.closest(".cms-content-view"),a=0!==n.closest(".cms-content-tools").length,i=a?n.data("url-listviewroot"):n.data("url-listview")+location.search,r=e.path.addSearchParams(i,{ParentID:t});if(a)return void(window.location=r);n.data("url",r),n.entwine(".ss").redraw()},getTreeConfig:function(){var t=this,n=this._super();return this.getHints(),n.plugins.push("contextmenu"),n.contextmenu={items:function(n){var a={edit:{label:n.hasClass("edit-disabled")?s.default._t("CMS.EditPage","Edit page",100,"Used in the context menu when right-clicking on a page node in the CMS tree"):s.default._t("CMS.ViewPage","View page",100,"Used in the context menu when right-clicking on a page node in the CMS tree"),action:function(n){e(".cms-container").entwine(".ss").loadPanel(s.default.sprintf(t.data("urlEditpage"),n.data("id")))}}};n.hasClass("nochildren")||(a.showaslist={label:s.default._t("CMS.ShowAsList"),action:function(e){t.showListViewFor(e.data("id"))}});var i=(n.data("pagetype"),n.data("id")),r=n.find(">a .item").data("allowedchildren"),o={},d=!1;return e.each(r,function(n,a){d=!0,o["allowedchildren-"+n]={label:'<span class="jstree-pageicon"></span>'+a,_class:"class-"+n.replace(/[^a-zA-Z0-9\-_:.]+/g,"_"),action:function(a){e(".cms-container").entwine(".ss").loadPanel(e.path.addSearchParams(s.default.sprintf(t.data("urlAddpage"),i,n),t.data("extraParams")))}}}),d&&(a.addsubpage={label:s.default._t("CMS.AddSubPage","Add page under this page",100,"Used in the context menu when right-clicking on a page node in the CMS tree"),submenu:o}),n.hasClass("edit-disabled")||(a.duplicate={label:s.default._t("CMS.Duplicate"),submenu:[{label:s.default._t("CMS.ThisPageOnly"),action:function(n){e(".cms-container").entwine(".ss").loadPanel(e.path.addSearchParams(s.default.sprintf(t.data("urlDuplicate"),n.data("id")),t.data("extraParams")))}},{label:s.default._t("CMS.ThisPageAndSubpages"),action:function(n){e(".cms-container").entwine(".ss").loadPanel(e.path.addSearchParams(s.default.sprintf(t.data("urlDuplicatewithchildren"),n.data("id")),t.data("extraParams")))}}]}),a}},n}}),e(".cms-tree a.jstree-clicked").entwine({onmatch:function(){var e,t=this,n=t.parents(".cms-panel-content");(t.offset().top<0||t.offset().top>n.height()-t.height())&&(e=n.scrollTop()+t.offset().top+n.height()/2,n.animate({scrollTop:e},"slow"))}}),e(".cms-tree-filtered .clear-filter").entwine({onclick:function(){window.location=location.protocol+"//"+location.host+location.pathname}}),e(".cms-tree .subtree-list-link").entwine({onclick:function(e){e.preventDefault(),localStorage.setItem("ss.pages-view-type","listview"),this.closest(".cms-tree").showListViewFor(this.data("id"))}})})},function(e,t,n){"use strict";var a=n(0);(function(e){return e&&e.__esModule?e:{default:e}})(a).default.entwine("ss",function(e){e(".cms-content-header-info").entwine({"from .cms-panel":{ontoggle:function(e){var t=this.closest(".cms-content").find(e.target);0!==t.length&&this.parent()[t.hasClass("collapsed")?"addClass":"removeClass"]("collapsed")}}}),e(".cms-panel-deferred.cms-content-view").entwine({onadd:function(){if(!this.data("no-ajax")){var e=localStorage.getItem("ss.pages-view-type")||"treeview";this.closest(".cms-content-tools").length>0&&(e="treeview");var t=this.data("url-"+e);this.data("deferredNoCache","listview"===e),this.data("url",t+location.search),this._super()}}}),e(".cms .page-view-link").entwine({onclick:function(t){t.preventDefault();var n=e(this).data("view"),a=this.closest(".cms-content-view"),i=a.data("url-"+n),r=0!==a.closest(".cms-content-tools").length;if(localStorage.setItem("ss.pages-view-type",n),r&&"listview"===n)return void(window.location=a.data("url-listviewroot"));a.data("url",i+location.search),a.redraw()}}),e(".cms .cms-clear-filter").entwine({onclick:function(t){t.preventDefault(),window.location=e(this).prop("href")}}),e(".cms-content-toolbar").entwine({onmatch:function(){var t=this;this._super(),e.each(this.find(".cms-actions-buttons-row .tool-button"),function(){var n=e(this),a=n.data("toolid");n.hasClass("active"),void 0!==a&&(n.data("active",!1).removeClass("active"),e("#"+a).hide(),t.bindActionButtonEvents(n))})},onunmatch:function(){var t=this;this._super(),e.each(this.find(".cms-actions-buttons-row .tool-button"),function(){var n=e(this);t.unbindActionButtonEvents(n)})},bindActionButtonEvents:function(e){var t=this;e.on("click.cmsContentToolbar",function(n){t.showHideTool(e)})},unbindActionButtonEvents:function(e){e.off(".cmsContentToolbar")},showHideTool:function(t){var n=t.data("active"),a=t.data("toolid"),i=e("#"+a);e.each(this.find(".cms-actions-buttons-row .tool-button"),function(){var t=e(this),n=e("#"+t.data("toolid"));t.data("toolid")!==a&&(n.hide(),t.data("active",!1))}),t[n?"removeClass":"addClass"]("active"),i[n?"hide":"show"](),t.data("active",!n)}})})},function(e,t,n){"use strict";function a(e){return e&&e.__esModule?e:{default:e}}var i=n(0),r=a(i),o=n(1),s=a(o);r.default.entwine("ss",function(e){e("#Form_VersionsForm").entwine({onmatch:function(){this._super()},onunmatch:function(){this._super()},onsubmit:function(t){t.preventDefault();var n=this.find(":input[name=ID]").val();if(!n)return!1;var a=null,i=null,r=null,o=this.find(":input[name=CompareMode]").is(":checked"),d=this.find("table input[type=checkbox]").filter(":checked");if(o){if(2!==d.length)return!1;i=d.eq(0).val(),r=d.eq(1).val(),a=s.default.sprintf(this.data("linkTmplCompare"),n,r,i)}else i=d.eq(0).val(),a=s.default.sprintf(this.data("linkTmplShow"),n,i);return e(".cms-container").loadPanel(a,"",{pjax:"CurrentForm"}),!0}}),e("#Form_VersionsForm input[name=ShowUnpublished]").entwine({onmatch:function(){this.toggle(),this._super()},onunmatch:function(){this._super()},onchange:function(){this.toggle()},toggle:function(){var t=e(this),n=t.parents("form").find("tr[data-published=false]");t.attr("checked")?n.removeClass("ui-helper-hidden").show():n.addClass("ui-helper-hidden").hide()._unselect()}}),e("#Form_VersionsForm tbody tr").entwine({onclick:function(){var e=this.parents("form").find(":input[name=CompareMode]").attr("checked"),t=this.siblings(".active");return e&&this.hasClass("active")?void this._unselect():e?t.length>1?void alert(s.default._t("CMS.ONLYSELECTTWO","You can only compare two versions at this time.")):(this._select(),void(1===t.length&&this.parents("form").submit())):(this._select(),t._unselect(),void this.parents("form").submit())},_unselect:function(){this.get(0).classList.remove("active"),this.find(":input[type=checkbox][checked]").attr("checked",!1)},_select:function(){this.addClass("active"),this.find(":input[type=checkbox]").attr("checked",!0)}})})},function(e,t,n){"use strict";var a=n(0);(function(e){return e&&e.__esModule?e:{default:e}})(a).default.entwine("ss",function(e){e("#Form_EditForm_RedirectionType input").entwine({onmatch:function(){e(this).attr("checked")&&this.toggle(),this._super()},onunmatch:function(){this._super()},onclick:function(){this.toggle()},toggle:function(){"Internal"==e(this).attr("value")?(e("#Form_EditForm_ExternalURL_Holder").hide(),e("#Form_EditForm_LinkToID_Holder").show()):(e("#Form_EditForm_ExternalURL_Holder").show(),e("#Form_EditForm_LinkToID_Holder").hide())}})})},function(e,t,n){"use strict";var a=n(0);(function(e){return e&&e.__esModule?e:{default:e}})(a).default.entwine("ss",function(e){e(".field.urlsegment:not(.readonly)").entwine({MaxPreviewLength:55,Ellipsis:"...",onmatch:function(){this.find(":text").length&&this.toggleEdit(!1),this.redraw(),this._super()},redraw:function(){var e=this.find(":text"),t=decodeURI(e.data("prefix")+e.val()),n=t;t.length>this.getMaxPreviewLength()&&(n=this.getEllipsis()+t.substr(t.length-this.getMaxPreviewLength(),t.length)),this.find(".URL-link").attr("href",encodeURI(t+e.data("suffix"))).text(n)},toggleEdit:function(e){var t=this.find(":text");this.find(".preview-holder")[e?"hide":"show"](),this.find(".edit-holder")[e?"show":"hide"](),e&&(t.data("origval",t.val()),t.focus())},update:function(){var e=this,t=this.find(":text"),n=t.data("origval"),a=arguments[0],i=a&&""!==a?a:t.val();n!=i?(this.addClass("loading"),this.suggest(i,function(n){t.val(decodeURIComponent(n.value)),e.toggleEdit(!1),e.removeClass("loading"),e.redraw()})):(this.toggleEdit(!1),this.redraw())},cancel:function(){var e=this.find(":text");e.val(e.data("origval")),this.toggleEdit(!1)},suggest:function(t,n){var a=this,i=a.find(":text"),r=e.path.parseUrl(a.closest("form").attr("action")),o=r.hrefNoSearch+"/field/"+i.attr("name")+"/suggest/?value="+encodeURIComponent(t);r.search&&(o+="&"+r.search.replace(/^\?/,"")),e.ajax({url:o,success:function(e){n.apply(this,arguments)},error:function(e,t){e.statusText=e.responseText},complete:function(){a.removeClass("loading")}})}}),e(".field.urlsegment .edit").entwine({onclick:function(e){e.preventDefault(),this.closest(".field").toggleEdit(!0)}}),e(".field.urlsegment .update").entwine({onclick:function(e){e.preventDefault(),this.closest(".field").update()}}),e(".field.urlsegment .cancel").entwine({onclick:function(e){e.preventDefault(),this.closest(".field").cancel()}})})},function(e,t,n){"use strict";function a(e){return e&&e.__esModule?e:{default:e}}Object.defineProperty(t,"__esModule",{value:!0});var i=n(2),r=a(i),o=n(25),s=a(o),d=n(31),l=a(d),u=n(32),c=a(u);t.default=function(){r.default.component.register("AnchorSelectorField",s.default),r.default.transform("pages-history",function(e){e.component("HistoryViewer.pages-controller-cms-content",l.default,"PageHistoryViewer")}),r.default.transform("pages-history-revert",function(e){e.component("HistoryViewerToolbar.VersionedAdmin.HistoryViewer.SiteTree.HistoryViewerVersionDetail",c.default,"PageRevertMutation")})}},function(e,t,n){"use strict";function a(e){return e&&e.__esModule?e:{default:e}}Object.defineProperty(t,"__esModule",{value:!0});var i=n(2),r=a(i),o=n(13),s=n(30),d=a(s);t.default=function(){r.default.reducer.register("cms",(0,o.combineReducers)({anchorSelector:d.default}))}},function(e,t,n){"use strict";n(15),n(16),n(18),n(17),n(19),n(20),n(21),n(14)},function(e,t,n){"use strict";function a(e){return e&&e.__esModule?e:{default:e}}function i(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function r(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function o(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function s(e,t){var n=(0,_.formValueSelector)(t.formid,I.default),a=t&&t.data&&t.data.targetFieldName||"PageID",i=Number(n(e,a)||0),r=[],o=i?e.cms.anchorSelector.pages.find(function(e){return e.id===i}):null;o&&o.loadingState===P.default.SUCCESS&&(r=o.anchors);var s=null;return s=o?o.loadingState:i?P.default.DIRTY:P.default.SUCCESS,{pageId:i,anchors:r,loadingState:s}}function d(e){return{actions:{anchorSelector:(0,v.bindActionCreators)(S,e)}}}Object.defineProperty(t,"__esModule",{value:!0}),t.ConnectedAnchorSelectorField=t.Component=void 0;var l=function(){function e(e,t){for(var n=0;n<t.length;n++){var a=t[n];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(e,a.key,a)}}return function(t,n,a){return n&&e(t.prototype,n),a&&e(t,a),t}}(),u=n(1),c=a(u),f=n(4),h=a(f),p=n(35),m=a(p),g=n(5),v=n(13),_=n(37),b=n(38),C=a(b),w=n(29),S=function(e){if(e&&e.__esModule)return e;var t={};if(null!=e)for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n]);return t.default=e,t}(w),y=n(11),P=a(y),T=n(34),E=a(T),F=n(36),A=n(40),I=a(A),D=n(39),O=a(D),x=function(){return null},L=function(e){function t(e){i(this,t);var n=r(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return n.handleChange=n.handleChange.bind(n),n.handleLoadingError=n.handleLoadingError.bind(n),n}return o(t,e),l(t,[{key:"componentDidMount",value:function(){this.ensurePagesLoaded()}},{key:"componentWillReceiveProps",value:function(e){this.props.pageId!==e.pageId&&this.ensurePagesLoaded(e)}},{key:"ensurePagesLoaded",value:function(){var e=this,t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:this.props;if(t.loadingState!==P.default.DIRTY||!t.pageId)return Promise.resolve();t.actions.anchorSelector.beginUpdating(t.pageId);var n=t.data.endpoint.replace(/:id/,t.pageId);return(0,m.default)(n,{credentials:"same-origin"}).then(function(e){return e.json()}).then(function(e){return t.actions.anchorSelector.updated(t.pageId,e),e}).catch(function(n){t.actions.anchorSelector.updateFailed(t.pageId),e.handleLoadingError(n,t)})}},{key:"getDropdownOptions",value:function(){var e=this,t=this.props.anchors.map(function(e){return{value:e}});return this.props.value&&!this.props.anchors.find(function(t){return t===e.props.value})&&t.unshift({value:this.props.value}),t}},{key:"handleChange",value:function(e){"function"==typeof this.props.onChange&&this.props.onChange(e?e.value:"")}},{key:"handleLoadingError",value:function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:this.props;if(t.onLoadingError===x)throw e;return t.onLoadingError({errors:[{value:e.message,type:"error"}]})}},{key:"render",value:function(){var e={id:this.props.id},t=(0,O.default)("anchorselectorfield",this.props.extraClass),n=this.getDropdownOptions(),a=this.props.value||"",i=c.default._t("CMS.ANCHOR_SELECT_OR_TYPE","Select or enter anchor");return h.default.createElement(F.Creatable,{searchable:!0,options:n,className:t,name:this.props.name,inputProps:e,onChange:this.handleChange,onBlurResetsInput:!0,value:a,placeholder:i,labelKey:"value"})}}]),t}(C.default);L.propTypes={extraClass:h.default.PropTypes.string,id:h.default.PropTypes.string,name:h.default.PropTypes.string.isRequired,onChange:h.default.PropTypes.func,value:h.default.PropTypes.string,attributes:h.default.PropTypes.oneOfType([h.default.PropTypes.object,h.default.PropTypes.array]),pageId:h.default.PropTypes.number,anchors:h.default.PropTypes.array,loadingState:h.default.PropTypes.oneOf(Object.keys(P.default).map(function(e){return P.default[e]})),onLoadingError:h.default.PropTypes.func,data:h.default.PropTypes.shape({endpoint:h.default.PropTypes.string,targetFieldName:h.default.PropTypes.string})},L.defaultProps={value:"",extraClass:"",onLoadingError:x,attributes:{}};var R=(0,g.connect)(s,d)(L);t.Component=L,t.ConnectedAnchorSelectorField=R,t.default=(0,E.default)(R)},,,,function(e,t,n){"use strict";function a(e){return{type:s.default.ANCHORSELECTOR_UPDATING,payload:{pageId:e}}}function i(e,t){return{type:s.default.ANCHORSELECTOR_UPDATED,payload:{pageId:e,anchors:t}}}function r(e){return{type:s.default.ANCHORSELECTOR_UPDATE_FAILED,payload:{pageId:e}}}Object.defineProperty(t,"__esModule",{value:!0}),t.beginUpdating=a,t.updated=i,t.updateFailed=r;var o=n(10),s=function(e){return e&&e.__esModule?e:{default:e}}(o)},function(e,t,n){"use strict";function a(e){return e&&e.__esModule?e:{default:e}}function i(e){if(Array.isArray(e)){for(var t=0,n=Array(e.length);t<e.length;t++)n[t]=e[t];return n}return Array.from(e)}function r(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:f,t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null,n=function(n,a){var r=t.payload.pageId;return(0,s.default)({pages:[].concat(i(e.pages.filter(function(e){return e.id!==r})),[{id:r,loadingState:n,anchors:a}]).sort(function(e,t){return e.id-t.id})})};switch(t.type){case l.default.ANCHORSELECTOR_UPDATING:return n(c.default.UPDATING,[]);case l.default.ANCHORSELECTOR_UPDATED:return n(c.default.SUCCESS,t.payload.anchors);case l.default.ANCHORSELECTOR_UPDATE_FAILED:return n(c.default.FAILED,[]);default:return e}}Object.defineProperty(t,"__esModule",{value:!0}),t.default=r;var o=n(33),s=a(o),d=n(10),l=a(d),u=n(11),c=a(u),f=(0,s.default)({pages:[]})},function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.config=t.query=void 0;var a=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var a in n)Object.prototype.hasOwnProperty.call(n,a)&&(e[a]=n[a])}return e},i=function(e,t){return Object.freeze(Object.defineProperties(e,{raw:{value:Object.freeze(t)}}))}(["\nquery ReadHistoryViewerPage ($page_id: ID!, $limit: Int!, $offset: Int!) {\n  readOnePage(\n    Versioning: {\n      Mode: LATEST\n    },\n    ID: $page_id\n  ) {\n    ID\n    Versions (limit: $limit, offset: $offset) {\n      pageInfo {\n        totalCount\n      }\n      edges {\n        node {\n          Version\n          AbsoluteLink\n          Author {\n            FirstName\n            Surname\n          }\n          Publisher {\n            FirstName\n            Surname\n          }\n          Published\n          LiveVersion\n          LastEdited\n        }\n      }\n    }\n  }\n}\n"],["\nquery ReadHistoryViewerPage ($page_id: ID!, $limit: Int!, $offset: Int!) {\n  readOnePage(\n    Versioning: {\n      Mode: LATEST\n    },\n    ID: $page_id\n  ) {\n    ID\n    Versions (limit: $limit, offset: $offset) {\n      pageInfo {\n        totalCount\n      }\n      edges {\n        node {\n          Version\n          AbsoluteLink\n          Author {\n            FirstName\n            Surname\n          }\n          Publisher {\n            FirstName\n            Surname\n          }\n          Published\n          LiveVersion\n          LastEdited\n        }\n      }\n    }\n  }\n}\n"]),r=n(3),o=n(12),s=function(e){return e&&e.__esModule?e:{default:e}}(o),d=(0,s.default)(i),l={options:function(e){var t=e.recordId,n=e.limit;return{variables:{limit:n,offset:((e.page||1)-1)*n,page_id:t}}},props:function(e){var t=e.data,n=t.error,i=t.refetch,r=t.readOnePage,o=t.loading,s=e.ownProps,d=s.actions,l=void 0===d?{versions:{}}:d,u=s.limit,c=s.recordId,f=r||null,h=n&&n.graphQLErrors&&n.graphQLErrors.map(function(e){return e.message});return{loading:o||!f,versions:f,graphQLErrors:h,actions:a({},l,{versions:a({},f,{goToPage:function(e){i({offset:((e||1)-1)*u,limit:u,page_id:c})}})})}}};t.query=d,t.config=l,t.default=(0,r.graphql)(d,l)},function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.config=t.mutation=void 0;var a=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var a in n)Object.prototype.hasOwnProperty.call(n,a)&&(e[a]=n[a])}return e},i=function(e,t){return Object.freeze(Object.defineProperties(e,{raw:{value:Object.freeze(t)}}))}(["\nmutation revertPageToVersion($id:ID!, $fromStage:VersionedStage!, $toStage:VersionedStage!, $fromVersion:Int!) {\n  copySilverStripeSiteTreeToStage(Input: {\n    ID: $id\n    FromVersion: $fromVersion\n    FromStage: $fromStage\n    ToStage: $toStage\n  }) {\n    ID\n  }\n}\n\n"],["\nmutation revertPageToVersion($id:ID!, $fromStage:VersionedStage!, $toStage:VersionedStage!, $fromVersion:Int!) {\n  copySilverStripeSiteTreeToStage(Input: {\n    ID: $id\n    FromVersion: $fromVersion\n    FromStage: $fromStage\n    ToStage: $toStage\n  }) {\n    ID\n  }\n}\n\n"]),r=n(3),o=n(12),s=function(e){return e&&e.__esModule?e:{default:e}}(o),d=(0,s.default)(i),l={props:function(e){var t=e.mutate,n=e.ownProps.actions;return{actions:a({},n,{revertToVersion:function(e,n,a,i){return t({variables:{id:e,fromVersion:n,fromStage:a,toStage:i}})}})}},options:{refetchQueries:["ReadHistoryViewerPage"]}};t.mutation=d,t.config=l,t.default=(0,r.graphql)(d,l)},function(e,t){e.exports=DeepFreezeStrict},function(e,t){e.exports=FieldHolder},function(e,t){e.exports=IsomorphicFetch},function(e,t){e.exports=ReactSelect},function(e,t){e.exports=ReduxForm},function(e,t){e.exports=SilverStripeComponent},function(e,t){e.exports=classnames},function(e,t){e.exports=getFormState}]);
>>>>>>> Fix show in list on detail view
