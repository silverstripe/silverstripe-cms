(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.CMSMain.AddForm', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssCMSMainAddForm = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	_jQuery2.default.entwine('ss', function ($) {
		$(".cms-add-form .parent-mode :input").entwine({
			onclick: function onclick(e) {
				if (this.val() == 'top') {
					var parentField = this.closest('form').find('#Form_AddForm_ParentID_Holder .TreeDropdownField');
					parentField.setValue('');
					parentField.setTitle('');
				}
			}
		});

		$(".cms-add-form").entwine({
			ParentID: 0,
			ParentCache: {},
			onadd: function onadd() {
				var self = this;
				this.find('#Form_AddForm_ParentID_Holder .TreeDropdownField').bind('change', function () {
					self.updateTypeList();
				});
				this.find(".SelectionGroup.parent-mode").bind('change', function () {
					self.updateTypeList();
				});
				this.updateTypeList();
			},
			loadCachedChildren: function loadCachedChildren(parentID) {
				var cache = this.getParentCache();
				if (typeof cache[parentID] !== 'undefined') return cache[parentID];else return null;
			},
			saveCachedChildren: function saveCachedChildren(parentID, children) {
				var cache = this.getParentCache();
				cache[parentID] = children;
				this.setParentCache(cache);
			},

			updateTypeList: function updateTypeList() {
				var hints = this.data('hints'),
				    parentTree = this.find('#Form_AddForm_ParentID_Holder .TreeDropdownField'),
				    parentMode = this.find("input[name=ParentModeField]:checked").val(),
				    metadata = parentTree.data('metadata'),
				    id = metadata && parentMode === 'child' ? parentTree.getValue() || this.getParentID() : null,
				    newClassName = metadata ? metadata.ClassName : null,
				    hintKey = newClassName && parentMode === 'child' ? newClassName : 'Root',
				    hint = typeof hints[hintKey] !== 'undefined' ? hints[hintKey] : null,
				    self = this,
				    defaultChildClass = hint && typeof hint.defaultChild !== 'undefined' ? hint.defaultChild : null,
				    disallowedChildren = [];

				if (id) {
					if (this.hasClass('loading')) return;
					this.addClass('loading');

					this.setParentID(id);
					if (!parentTree.getValue()) parentTree.setValue(id);

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
					disallowedChildren = hint && typeof hint.disallowedChildren !== 'undefined' ? hint.disallowedChildren : [], this.updateSelectionFilter(disallowedChildren, defaultChildClass);
				}
			},

			updateSelectionFilter: function updateSelectionFilter(disallowedChildren, defaultChildClass) {
				var allAllowed = null;
				this.find('#Form_AddForm_PageType li').each(function () {
					var className = $(this).find('input').val(),
					    isAllowed = $.inArray(className, disallowedChildren) === -1;

					$(this).setEnabled(isAllowed);
					if (!isAllowed) $(this).setSelected(false);
					if (allAllowed === null) allAllowed = isAllowed;else allAllowed = allAllowed && isAllowed;
				});

				if (defaultChildClass) {
					var selectedEl = this.find('#Form_AddForm_PageType li input[value=' + defaultChildClass + ']').parents('li:first');
				} else {
					var selectedEl = this.find('#Form_AddForm_PageType li:not(.disabled):first');
				}
				selectedEl.setSelected(true);
				selectedEl.siblings().setSelected(false);

				var buttonState = this.find('#Form_AddForm_PageType li:not(.disabled)').length ? 'enable' : 'disable';
				this.find('button[name=action_doAdd]').button(buttonState);

				this.find('.message-restricted')[allAllowed ? 'hide' : 'show']();
			}
		});

		$(".cms-add-form #Form_AddForm_PageType li").entwine({
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
				if (!bool) $(this).find('input').attr('disabled', 'disabled').removeAttr('checked');else $(this).find('input').removeAttr('disabled');
			}
		});

		$(".cms-page-add-button").entwine({
			onclick: function onclick(e) {
				var tree = $('.cms-tree'),
				    list = $('.cms-list'),
				    parentId = 0;

				if (tree.is(':visible')) {
					var selected = tree.jstree('get_selected');
					parentId = selected ? $(selected[0]).data('id') : null;
				} else {
					var state = list.find('input[name="Page[GridState]"]').val();
					if (state) parentId = parseInt(JSON.parse(state).ParentID, 10);
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
});