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
/******/ 	return __webpack_require__(__webpack_require__.s = "./client/src/bundles/bundle.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./client/src/boot/index.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _registerReducers = __webpack_require__("./client/src/boot/registerReducers.js");

var _registerReducers2 = _interopRequireDefault(_registerReducers);

var _registerComponents = __webpack_require__("./client/src/boot/registerComponents.js");

var _registerComponents2 = _interopRequireDefault(_registerComponents);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

window.document.addEventListener('DOMContentLoaded', function () {
  (0, _registerComponents2.default)();
  (0, _registerReducers2.default)();
});

/***/ }),

/***/ "./client/src/boot/registerComponents.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(3);

var _Injector2 = _interopRequireDefault(_Injector);

var _AnchorSelectorField = __webpack_require__("./client/src/components/AnchorSelectorField/AnchorSelectorField.js");

var _AnchorSelectorField2 = _interopRequireDefault(_AnchorSelectorField);

var _readOnePageQuery = __webpack_require__("./client/src/state/history/readOnePageQuery.js");

var _readOnePageQuery2 = _interopRequireDefault(_readOnePageQuery);

var _rollbackPageMutation = __webpack_require__("./client/src/state/history/rollbackPageMutation.js");

var _rollbackPageMutation2 = _interopRequireDefault(_rollbackPageMutation);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.default = function () {
  _Injector2.default.component.register('AnchorSelectorField', _AnchorSelectorField2.default);

  _Injector2.default.transform('pages-history', function (updater) {
    updater.component('HistoryViewer.pages-controller-cms-content', _readOnePageQuery2.default, 'PageHistoryViewer');
  });

  _Injector2.default.transform('pages-history-revert', function (updater) {
    updater.component('HistoryViewerToolbar.VersionedAdmin.HistoryViewer.SiteTree.HistoryViewerVersionDetail', _rollbackPageMutation2.default, 'PageRevertMutation');
  });
};

/***/ }),

/***/ "./client/src/boot/registerReducers.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(3);

var _Injector2 = _interopRequireDefault(_Injector);

var _redux = __webpack_require__(12);

var _AnchorSelectorReducer = __webpack_require__("./client/src/state/anchorSelector/AnchorSelectorReducer.js");

var _AnchorSelectorReducer2 = _interopRequireDefault(_AnchorSelectorReducer);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.default = function () {
  _Injector2.default.reducer.register('cms', (0, _redux.combineReducers)({
    anchorSelector: _AnchorSelectorReducer2.default
  }));
};

/***/ }),

/***/ "./client/src/bundles/bundle.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__("./client/src/legacy/CMSMain.AddForm.js");
__webpack_require__("./client/src/legacy/CMSMain.EditForm.js");
__webpack_require__("./client/src/legacy/CMSMain.js");
__webpack_require__("./client/src/legacy/CMSMain.Tree.js");
__webpack_require__("./client/src/legacy/CMSPageHistoryController.js");
__webpack_require__("./client/src/legacy/RedirectorPage.js");
__webpack_require__("./client/src/legacy/SiteTreeURLSegmentField.js");

__webpack_require__("./client/src/boot/index.js");

/***/ }),

/***/ "./client/src/components/AnchorSelectorField/AnchorSelectorField.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ConnectedAnchorSelectorField = exports.Component = undefined;

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _i18n = __webpack_require__(1);

var _i18n2 = _interopRequireDefault(_i18n);

var _react = __webpack_require__(2);

var _react2 = _interopRequireDefault(_react);

var _isomorphicFetch = __webpack_require__(15);

var _isomorphicFetch2 = _interopRequireDefault(_isomorphicFetch);

var _reactRedux = __webpack_require__(6);

var _redux = __webpack_require__(12);

var _reduxForm = __webpack_require__(18);

var _SilverStripeComponent = __webpack_require__(19);

var _SilverStripeComponent2 = _interopRequireDefault(_SilverStripeComponent);

var _AnchorSelectorActions = __webpack_require__("./client/src/state/anchorSelector/AnchorSelectorActions.js");

var anchorSelectorActions = _interopRequireWildcard(_AnchorSelectorActions);

var _AnchorSelectorStates = __webpack_require__("./client/src/state/anchorSelector/AnchorSelectorStates.js");

var _AnchorSelectorStates2 = _interopRequireDefault(_AnchorSelectorStates);

var _FieldHolder = __webpack_require__(14);

var _FieldHolder2 = _interopRequireDefault(_FieldHolder);

var _reactSelect = __webpack_require__(16);

var _getFormState = __webpack_require__(21);

var _getFormState2 = _interopRequireDefault(_getFormState);

var _classnames = __webpack_require__(20);

var _classnames2 = _interopRequireDefault(_classnames);

var _propTypes = __webpack_require__(11);

var _propTypes2 = _interopRequireDefault(_propTypes);

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _asyncToGenerator(fn) { return function () { var gen = fn.apply(this, arguments); return new Promise(function (resolve, reject) { function step(key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { return Promise.resolve(value).then(function (value) { step("next", value); }, function (err) { step("throw", err); }); } } return step("next"); }); }; }

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

    _this.state = {
      anchors: []
    };

    _this.handleChange = _this.handleChange.bind(_this);
    _this.handleLoadingError = _this.handleLoadingError.bind(_this);
    return _this;
  }

  _createClass(AnchorSelectorField, [{
    key: 'componentDidMount',
    value: function componentDidMount() {
      this.fetchAnchors();
    }
  }, {
    key: 'componentDidUpdate',
    value: function componentDidUpdate(prevProps) {
      if (this.props.pageId !== prevProps.pageId) {
        this.fetchAnchors();
      }
    }
  }, {
    key: 'fetchAnchors',
    value: function fetchAnchors() {
      var _this2 = this;

      var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.props;

      if (!props.pageId) {
        return;
      }
      var doFetch = function () {
        var _ref = _asyncToGenerator(regeneratorRuntime.mark(function _callee() {
          var fetchURL, response, anchors;
          return regeneratorRuntime.wrap(function _callee$(_context) {
            while (1) {
              switch (_context.prev = _context.next) {
                case 0:
                  fetchURL = props.data.endpoint.replace(/:id/, props.pageId);
                  _context.next = 3;
                  return (0, _isomorphicFetch2.default)(fetchURL, { credentials: 'same-origin' });

                case 3:
                  response = _context.sent;
                  anchors = [];

                  if (!response.ok) {
                    _context.next = 9;
                    break;
                  }

                  _context.next = 8;
                  return response.json();

                case 8:
                  anchors = _context.sent;

                case 9:
                  return _context.abrupt('return', Promise.resolve(anchors));

                case 10:
                case 'end':
                  return _context.stop();
              }
            }
          }, _callee, _this2);
        }));

        return function doFetch() {
          return _ref.apply(this, arguments);
        };
      }();
      doFetch().then(function (anchors) {
        _this2.setState({ anchors: anchors });
      }).catch(function (error) {
        return _this2.handleLoadingError(error, props);
      });
    }
  }, {
    key: 'getDropdownOptions',
    value: function getDropdownOptions() {
      var _this3 = this;

      var options = this.state.anchors.map(function (value) {
        return { value: value };
      });

      if (this.props.value && !this.state.anchors.find(function (value) {
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
  extraClass: _propTypes2.default.string,
  id: _propTypes2.default.string,
  name: _propTypes2.default.string.isRequired,
  onChange: _propTypes2.default.func,
  value: _propTypes2.default.string,
  attributes: _propTypes2.default.oneOfType([_propTypes2.default.object, _propTypes2.default.array]),
  pageId: _propTypes2.default.number,
  loadingState: _propTypes2.default.oneOf(Object.keys(_AnchorSelectorStates2.default).map(function (key) {
    return _AnchorSelectorStates2.default[key];
  })),
  onLoadingError: _propTypes2.default.func,
  data: _propTypes2.default.shape({
    endpoint: _propTypes2.default.string,
    targetFieldName: _propTypes2.default.string
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
  return { pageId: pageId };
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

/***/ "./client/src/legacy/CMSMain.AddForm.js":
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
      var currentSelection = this.find('#Form_AddForm_PageType div.radio.selected')[0];
      var keepSelection = false;

      var allAllowed = null;
      this.find('#Form_AddForm_PageType div.radio').each(function (i, el) {
        var className = $(this).find('input').val(),
            isAllowed = $.inArray(className, disallowedChildren) === -1;

        if (el === currentSelection && isAllowed) {
          keepSelection = true;
        }

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

      if (keepSelection) {
        var selectedEl = $(currentSelection).parents('li:first');
      } else if (defaultChildClass) {
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

/***/ "./client/src/legacy/CMSMain.EditForm.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _i18n = __webpack_require__(1);

var _i18n2 = _interopRequireDefault(_i18n);

var _reactstrapConfirm = __webpack_require__("./node_modules/@silverstripe/reactstrap-confirm/dist/index.js");

var _reactstrapConfirm2 = _interopRequireDefault(_reactstrapConfirm);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _asyncToGenerator(fn) { return function () { var gen = fn.apply(this, arguments); return new Promise(function (resolve, reject) { function step(key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { return Promise.resolve(value).then(function (value) { step("next", value); }, function (err) { step("throw", err); }); } } return step("next"); }); }; }

_jquery2.default.entwine('ss', function ($) {
	$('.cms-edit-form :input#Form_EditForm_ClassName').entwine({
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
						$('.update', self.parent()).show().parent('.form__field-holder').addClass('input-group');
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
				updateURLFromTitle.hide().parent('.form__field-holder').removeClass('input-group');
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

			this.parents('form:first').addClass('loading');
			return this._super(e);
		}
	});

	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_archive:not(.homepage-warning)').entwine({
		onclick: function onclick(e) {
			var form = this.parents('form:first'),
			    message = '';
			message = form.find('input[name=ArchiveWarningMessage]').val().replace(/\\n/g, '\n');

			if (confirm(message)) {
				this.parents('form:first').addClass('loading');
				return this._super(e);
			}
			return false;
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
				this.parents('form:first').addClass('loading');
				return this._super(e);
			}
			return false;
		}
	});

	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_unpublish:not(.homepage-warning)').entwine({
		onclick: function onclick(e) {
			var form = this.parents('form:first'),
			    version = form.find(':input[name=Version]').val(),
			    message = '';
			message = _i18n2.default.sprintf(_i18n2.default._t('CMS.Unpublish'), version);
			if (confirm(message)) {
				this.parents('form:first').addClass('loading');
				return this._super(e);
			}
			return false;
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

	var confirmed = false;

	$('.cms-edit-form .btn-toolbar #Form_EditForm_action_unpublish.homepage-warning,' + '.cms-edit-form .btn-toolbar #Form_EditForm_action_archive.homepage-warning,' + '#Form_EditForm_URLSegment_Holder.homepage-warning .btn.update').entwine({
		onclick: function () {
			var _ref = _asyncToGenerator(regeneratorRuntime.mark(function _callee(e) {
				var message;
				return regeneratorRuntime.wrap(function _callee$(_context) {
					while (1) {
						switch (_context.prev = _context.next) {
							case 0:
								if (!confirmed) {
									_context.next = 2;
									break;
								}

								return _context.abrupt('return', this._super(e));

							case 2:
								e.stopPropagation();

								message = _i18n2.default._t('CMS.RemoveHomePageWarningMessage', 'Warning: This page is the home page. ' + 'By changing the URL segment visitors will not be able to view it.');
								_context.next = 6;
								return (0, _reactstrapConfirm2.default)(message, {
									title: _i18n2.default._t('CMS.RemoveHomePageWarningTitle', 'Remove your home page?'),
									confirmLabel: _i18n2.default._t('CMS.RemoveHomePageWarningLabel', 'Remove'),
									confirmColor: 'danger'
								});

							case 6:
								if (!_context.sent) {
									_context.next = 10;
									break;
								}

								confirmed = true;
								this.trigger('click');
								confirmed = false;

							case 10:
								return _context.abrupt('return', false);

							case 11:
							case 'end':
								return _context.stop();
						}
					}
				}, _callee, this);
			}));

			function onclick(_x) {
				return _ref.apply(this, arguments);
			}

			return onclick;
		}()
	});
});

/***/ }),

/***/ "./client/src/legacy/CMSMain.Tree.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _i18n = __webpack_require__(1);

var _i18n2 = _interopRequireDefault(_i18n);

var _reactstrapConfirm = __webpack_require__("./node_modules/@silverstripe/reactstrap-confirm/dist/index.js");

var _reactstrapConfirm2 = _interopRequireDefault(_reactstrapConfirm);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _asyncToGenerator(fn) { return function () { var gen = fn.apply(this, arguments); return new Promise(function (resolve, reject) { function step(key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { return Promise.resolve(value).then(function (value) { step("next", value); }, function (err) { step("throw", err); }); } } return step("next"); }); }; }

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

        $(menus[i]).addClass('vakata-col-' + col).removeClass('right');

        $(menus[i]).find('li').on("mouseenter", function (e) {
          $(this).parent('ul').removeClass("right");
        });
      });
    },

    showListViewFor: function showListViewFor(id) {
      localStorage.setItem('ss.pages-view-type', 'listview');
      var $contentView = this.closest('.cms-content-view');
      var url = $contentView.data('url-listviewroot');
      var urlWithParams = $.path.addSearchParams(url, {
        ParentID: id
      });

      var baseUrl = $('base').attr('href') || '';
      window.location.assign(baseUrl + urlWithParams);
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

          $.each(allowedChildren, function (index, child) {
            hasAllowedChildren = true;
            menuAllowedChildren["allowedchildren-" + child.ClassName] = {
              'label': '<span class="jstree-pageicon ' + child.IconClass + '"></span>' + child.Title,
              '_class': 'class-' + child.ClassName.replace(/[^a-zA-Z0-9\-_:.]+/g, '_'),
              'action': function action(obj) {
                $('.cms-container').entwine('.ss').loadPanel($.path.addSearchParams(_i18n2.default.sprintf(self.data('urlAddpage'), id, child.ClassName), self.data('extraParams')));
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
    },

    canMove: function () {
      var _ref = _asyncToGenerator(regeneratorRuntime.mark(function _callee(data) {
        var isHomePage, oldParentId, newParentId, message;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                isHomePage = data.rslt.o.find(".homepage").first().length > 0;

                if (isHomePage) {
                  _context.next = 3;
                  break;
                }

                return _context.abrupt('return', true);

              case 3:
                oldParentId = data.rslt.op.data('id');
                newParentId = data.rslt.np.data('id');

                if (!(oldParentId === newParentId)) {
                  _context.next = 7;
                  break;
                }

                return _context.abrupt('return', true);

              case 7:
                message = _i18n2.default._t('CMS.RemoveHomePageWarningMessage', 'Warning: This page is the home page. ' + 'By changing the URL segment visitors will not be able to view it.');
                _context.next = 10;
                return (0, _reactstrapConfirm2.default)(message, {
                  title: _i18n2.default._t('CMS.RemoveHomePageWarningTitle', 'Remove your home page?'),
                  confirmLabel: _i18n2.default._t('CMS.RemoveHomePageWarningLabel', 'Remove'),
                  confirmColor: 'danger'
                });

              case 10:
                return _context.abrupt('return', _context.sent);

              case 11:
              case 'end':
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function canMove(_x) {
        return _ref.apply(this, arguments);
      }

      return canMove;
    }()
  });

  $('.cms-tree a.jstree-clicked').entwine({
    onmatch: function onmatch() {
      var self = this,
          panel = self.parents('.cms-tree-view-sidebar');

      if (self.offset().top < 0 || self.offset().top > panel.height() - self.height()) {
        var scrollToElement = self.parent();

        if (scrollToElement.prev().length) {
          scrollToElement = scrollToElement.prev();
        }
        scrollToElement.get(0).scrollIntoView();
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

/***/ "./client/src/legacy/CMSMain.js":
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

			var clearFiltered = localStorage.getItem('ss.pages-view-filtered');
			if (typeof clearFiltered === 'string' && clearFiltered.toLowerCase() === 'false') {
				clearFiltered = false;
			}

			localStorage.setItem('ss.pages-view-filtered', false);

			this.data('deferredNoCache', clearFiltered || viewType === VIEW_TYPE_LIST);
			this.data('url', url + location.search);
			this._super();
		}
	});

	$('.js-injector-boot .search-holder--cms').entwine({
		search: function search(data) {
			localStorage.setItem('ss.pages-view-filtered', true);

			this._super(data);
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
				var baseUrl = $('base').attr('href') || '';
				window.location.assign(baseUrl + $contentView.data('url-listviewroot'));

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

/***/ "./client/src/legacy/CMSPageHistoryController.js":
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

/***/ "./client/src/legacy/RedirectorPage.js":
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

/***/ "./client/src/legacy/SiteTreeURLSegmentField.js":
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

	$('.field.urlsegment .text').entwine({
		onkeydown: function onkeydown(e) {
			if (e.keyCode === 13) {
				e.preventDefault();
				this.closest('.field').update();
			}
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

/***/ "./client/src/state/anchorSelector/AnchorSelectorActionTypes.js":
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

/***/ "./client/src/state/anchorSelector/AnchorSelectorActions.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.beginUpdating = beginUpdating;
exports.updated = updated;
exports.updateFailed = updateFailed;

var _AnchorSelectorActionTypes = __webpack_require__("./client/src/state/anchorSelector/AnchorSelectorActionTypes.js");

var _AnchorSelectorActionTypes2 = _interopRequireDefault(_AnchorSelectorActionTypes);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function beginUpdating(pageId) {
  return {
    type: _AnchorSelectorActionTypes2.default.ANCHORSELECTOR_UPDATING,
    payload: { pageId: pageId }
  };
}

function updated(pageId, anchors) {
  var cacheResult = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

  return {
    type: _AnchorSelectorActionTypes2.default.ANCHORSELECTOR_UPDATED,
    payload: { pageId: pageId, anchors: anchors, cacheResult: cacheResult }
  };
}

function updateFailed(pageId) {
  return {
    type: _AnchorSelectorActionTypes2.default.ANCHORSELECTOR_UPDATE_FAILED,
    payload: { pageId: pageId }
  };
}

/***/ }),

/***/ "./client/src/state/anchorSelector/AnchorSelectorReducer.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = anchorSelectorReducer;

var _deepFreezeStrict = __webpack_require__(13);

var _deepFreezeStrict2 = _interopRequireDefault(_deepFreezeStrict);

var _AnchorSelectorActionTypes = __webpack_require__("./client/src/state/anchorSelector/AnchorSelectorActionTypes.js");

var _AnchorSelectorActionTypes2 = _interopRequireDefault(_AnchorSelectorActionTypes);

var _AnchorSelectorStates = __webpack_require__("./client/src/state/anchorSelector/AnchorSelectorStates.js");

var _AnchorSelectorStates2 = _interopRequireDefault(_AnchorSelectorStates);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var initialState = (0, _deepFreezeStrict2.default)({ pages: [] });

function anchorSelectorReducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : initialState;
  var action = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

  var updatePage = function updatePage(loadingState, anchors) {
    console.log(action);
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
        var _action$payload = action.payload,
            anchors = _action$payload.anchors,
            cacheResult = _action$payload.cacheResult;
        var SUCCESS = _AnchorSelectorStates2.default.SUCCESS,
            DIRTY = _AnchorSelectorStates2.default.DIRTY;

        var newSelectorLoadingState = cacheResult ? SUCCESS : DIRTY;
        return updatePage(newSelectorLoadingState, anchors);
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

/***/ "./client/src/state/anchorSelector/AnchorSelectorStates.js":
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

/***/ "./client/src/state/history/readOnePageQuery.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.config = exports.query = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _templateObject = _taggedTemplateLiteral(['\nquery ReadHistoryViewerPage ($page_id: ID!, $limit: Int!, $offset: Int!) {\n  readOnePage(\n    versioning: {\n      mode: ALL_VERSIONS\n    },\n    filter: {\n      id: { eq: $page_id }\n    }\n  ) {\n    id\n    versions (limit: $limit, offset: $offset, sort: {\n      version: DESC\n    }) {\n      pageInfo {\n        totalCount\n      }\n      nodes {\n        version\n        absoluteLink\n        author {\n          firstName\n          surname\n        }\n        publisher {\n          firstName\n          surname\n        }\n        deleted\n        draft\n        published\n        liveVersion\n        latestDraftVersion\n        lastEdited\n      }\n    }\n  }\n}\n'], ['\nquery ReadHistoryViewerPage ($page_id: ID!, $limit: Int!, $offset: Int!) {\n  readOnePage(\n    versioning: {\n      mode: ALL_VERSIONS\n    },\n    filter: {\n      id: { eq: $page_id }\n    }\n  ) {\n    id\n    versions (limit: $limit, offset: $offset, sort: {\n      version: DESC\n    }) {\n      pageInfo {\n        totalCount\n      }\n      nodes {\n        version\n        absoluteLink\n        author {\n          firstName\n          surname\n        }\n        publisher {\n          firstName\n          surname\n        }\n        deleted\n        draft\n        published\n        liveVersion\n        latestDraftVersion\n        lastEdited\n      }\n    }\n  }\n}\n']);

var _reactApollo = __webpack_require__(4);

var _graphqlTag = __webpack_require__(10);

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
      },

      fetchPolicy: 'network-only'
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

/***/ "./client/src/state/history/rollbackPageMutation.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.config = exports.mutation = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _templateObject = _taggedTemplateLiteral(['\nmutation rollbackPage($id:ID!, $toVersion:Int!) {\n  rollbackPage(\n    id: $id\n    toVersion: $toVersion\n  ) {\n    id\n  }\n}\n'], ['\nmutation rollbackPage($id:ID!, $toVersion:Int!) {\n  rollbackPage(\n    id: $id\n    toVersion: $toVersion\n  ) {\n    id\n  }\n}\n']);

var _reactApollo = __webpack_require__(4);

var _graphqlTag = __webpack_require__(10);

var _graphqlTag2 = _interopRequireDefault(_graphqlTag);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _taggedTemplateLiteral(strings, raw) { return Object.freeze(Object.defineProperties(strings, { raw: { value: Object.freeze(raw) } })); }

var mutation = (0, _graphqlTag2.default)(_templateObject);

var config = {
  props: function props(_ref) {
    var mutate = _ref.mutate,
        actions = _ref.ownProps.actions;

    var rollbackPage = function rollbackPage(id, toVersion) {
      return mutate({
        variables: {
          id: id,
          toVersion: toVersion
        }
      });
    };

    return {
      actions: _extends({}, actions, {
        rollbackPage: rollbackPage,

        revertToVersion: rollbackPage
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

/***/ "./node_modules/@babel/runtime/helpers/extends.js":
/***/ (function(module, exports) {

function _extends() {
  module.exports = _extends = Object.assign || function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];

      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }

    return target;
  };

  return _extends.apply(this, arguments);
}

module.exports = _extends;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/inheritsLoose.js":
/***/ (function(module, exports) {

function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  subClass.__proto__ = superClass;
}

module.exports = _inheritsLoose;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/interopRequireDefault.js":
/***/ (function(module, exports) {

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : {
    "default": obj
  };
}

module.exports = _interopRequireDefault;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/interopRequireWildcard.js":
/***/ (function(module, exports) {

function _getRequireWildcardCache() {
  if (typeof WeakMap !== "function") return null;
  var cache = new WeakMap();

  _getRequireWildcardCache = function _getRequireWildcardCache() {
    return cache;
  };

  return cache;
}

function _interopRequireWildcard(obj) {
  if (obj && obj.__esModule) {
    return obj;
  }

  var cache = _getRequireWildcardCache();

  if (cache && cache.has(obj)) {
    return cache.get(obj);
  }

  var newObj = {};

  if (obj != null) {
    var hasPropertyDescriptor = Object.defineProperty && Object.getOwnPropertyDescriptor;

    for (var key in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) {
        var desc = hasPropertyDescriptor ? Object.getOwnPropertyDescriptor(obj, key) : null;

        if (desc && (desc.get || desc.set)) {
          Object.defineProperty(newObj, key, desc);
        } else {
          newObj[key] = obj[key];
        }
      }
    }
  }

  newObj["default"] = obj;

  if (cache) {
    cache.set(obj, newObj);
  }

  return newObj;
}

module.exports = _interopRequireWildcard;

/***/ }),

/***/ "./node_modules/@silverstripe/reactstrap-confirm/dist/Confirmation.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _interopRequireWildcard = __webpack_require__("./node_modules/@babel/runtime/helpers/interopRequireWildcard.js");

var _interopRequireDefault = __webpack_require__("./node_modules/@babel/runtime/helpers/interopRequireDefault.js");

exports.__esModule = true;
exports.default = void 0;

var _inheritsLoose2 = _interopRequireDefault(__webpack_require__("./node_modules/@babel/runtime/helpers/inheritsLoose.js"));

var _react = _interopRequireWildcard(__webpack_require__(2));

var _propTypes = _interopRequireDefault(__webpack_require__(11));

var _reactstrap = __webpack_require__(17);

/**
 * Renders a confirmation modal immediately with an onConfirm action. Used with `lib/confirm`.
 */
var Confirmation =
/*#__PURE__*/
function (_Component) {
  (0, _inheritsLoose2.default)(Confirmation, _Component);

  function Confirmation(props) {
    var _this;

    _this = _Component.call(this, props) || this;
    _this.state = {
      isOpen: true
    };
    return _this;
  }

  var _proto = Confirmation.prototype;

  _proto.render = function render() {
    var _this2 = this;

    var _this$props = this.props,
        onConfirm = _this$props.onConfirm,
        onCancel = _this$props.onCancel,
        title = _this$props.title,
        body = _this$props.body,
        confirmLabel = _this$props.confirmLabel,
        confirmColor = _this$props.confirmColor,
        dismissLabel = _this$props.dismissLabel,
        showDismissButton = _this$props.showDismissButton;
    var isOpen = this.state.isOpen;

    var handleToggle = function handleToggle() {
      if (typeof onCancel === 'function') {
        onCancel();
      }

      _this2.setState({
        isOpen: false
      });
    };

    var handleConfirm = function handleConfirm() {
      onConfirm();

      _this2.setState({
        isOpen: false
      });
    };

    return _react.default.createElement(_reactstrap.Modal, {
      isOpen: isOpen,
      toggle: handleToggle
    }, title && _react.default.createElement(_reactstrap.ModalHeader, {
      toggle: handleToggle
    }, title), _react.default.createElement(_reactstrap.ModalBody, null, body), _react.default.createElement(_reactstrap.ModalFooter, null, _react.default.createElement(_reactstrap.Button, {
      color: confirmColor,
      onClick: handleConfirm
    }, confirmLabel), (showDismissButton || !title) && _react.default.createElement(_reactstrap.Button, {
      onClick: handleToggle
    }, dismissLabel || 'Cancel')));
  };

  return Confirmation;
}(_react.Component);

Confirmation.propTypes = {
  onConfirm: _propTypes.default.func.isRequired,
  body: _propTypes.default.string.isRequired,
  onCancel: _propTypes.default.func,
  title: _propTypes.default.string,
  confirmLabel: _propTypes.default.string,
  confirmColor: _propTypes.default.string,
  dismissLabel: _propTypes.default.string
};
Confirmation.defaultProps = {
  confirmLabel: 'Confirm',
  confirmColor: 'primary'
};
var _default = Confirmation;
exports.default = _default;

/***/ }),

/***/ "./node_modules/@silverstripe/reactstrap-confirm/dist/confirm.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _interopRequireDefault = __webpack_require__("./node_modules/@babel/runtime/helpers/interopRequireDefault.js");

exports.__esModule = true;
exports.default = void 0;

var _extends2 = _interopRequireDefault(__webpack_require__("./node_modules/@babel/runtime/helpers/extends.js"));

var _react = _interopRequireDefault(__webpack_require__(2));

var _reactDom = _interopRequireDefault(__webpack_require__(5));

var _Confirmation = _interopRequireDefault(__webpack_require__("./node_modules/@silverstripe/reactstrap-confirm/dist/Confirmation.js"));

var confirmation = function confirmation(message, additionalProps, mountNode, unmountDelay, Component) {
  if (additionalProps === void 0) {
    additionalProps = {};
  }

  if (mountNode === void 0) {
    mountNode = document.body;
  }

  if (unmountDelay === void 0) {
    unmountDelay = 350;
  }

  var ConfirmComponent = Component || _Confirmation.default;
  var wrapper = mountNode.appendChild(document.createElement('div'));
  return new Promise(function (resolve) {
    var createCompleteHandler = function createCompleteHandler(result) {
      return function () {
        resolve(result);
        setTimeout(function () {
          _reactDom.default.unmountComponentAtNode(wrapper);

          setTimeout(function () {
            return mountNode.removeChild(wrapper);
          });
        }, unmountDelay);
      };
    };

    _reactDom.default.render(_react.default.createElement(ConfirmComponent, (0, _extends2.default)({}, additionalProps, {
      onConfirm: createCompleteHandler(true),
      onCancel: createCompleteHandler(false),
      body: message
    })), wrapper);
  });
};

var _default = confirmation;
exports.default = _default;

/***/ }),

/***/ "./node_modules/@silverstripe/reactstrap-confirm/dist/index.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _interopRequireDefault = __webpack_require__("./node_modules/@babel/runtime/helpers/interopRequireDefault.js");

exports.__esModule = true;
exports.default = void 0;

var _confirm = _interopRequireDefault(__webpack_require__("./node_modules/@silverstripe/reactstrap-confirm/dist/confirm.js"));

var _Confirmation = _interopRequireDefault(__webpack_require__("./node_modules/@silverstripe/reactstrap-confirm/dist/Confirmation.js"));

exports.Confirmation = _Confirmation.default;
var _default = _confirm.default;
exports.default = _default;

/***/ }),

/***/ 0:
/***/ (function(module, exports) {

module.exports = jQuery;

/***/ }),

/***/ 1:
/***/ (function(module, exports) {

module.exports = i18n;

/***/ }),

/***/ 10:
/***/ (function(module, exports) {

module.exports = GraphQLTag;

/***/ }),

/***/ 11:
/***/ (function(module, exports) {

module.exports = PropTypes;

/***/ }),

/***/ 12:
/***/ (function(module, exports) {

module.exports = Redux;

/***/ }),

/***/ 13:
/***/ (function(module, exports) {

module.exports = DeepFreezeStrict;

/***/ }),

/***/ 14:
/***/ (function(module, exports) {

module.exports = FieldHolder;

/***/ }),

/***/ 15:
/***/ (function(module, exports) {

module.exports = IsomorphicFetch;

/***/ }),

/***/ 16:
/***/ (function(module, exports) {

module.exports = ReactSelect;

/***/ }),

/***/ 17:
/***/ (function(module, exports) {

module.exports = Reactstrap;

/***/ }),

/***/ 18:
/***/ (function(module, exports) {

module.exports = ReduxForm;

/***/ }),

/***/ 19:
/***/ (function(module, exports) {

module.exports = SilverStripeComponent;

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

module.exports = React;

/***/ }),

/***/ 20:
/***/ (function(module, exports) {

module.exports = classnames;

/***/ }),

/***/ 21:
/***/ (function(module, exports) {

module.exports = getFormState;

/***/ }),

/***/ 3:
/***/ (function(module, exports) {

module.exports = Injector;

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

module.exports = ReactApollo;

/***/ }),

/***/ 5:
/***/ (function(module, exports) {

module.exports = ReactDom;

/***/ }),

/***/ 6:
/***/ (function(module, exports) {

module.exports = ReactRedux;

/***/ })

/******/ });
//# sourceMappingURL=bundle.js.map