(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.SiteTreeURLSegmentField', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssSiteTreeURLSegmentField = mod.exports;
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

				this.find('.preview').attr('href', encodeURI(url + field.data('suffix'))).text(previewUrl);
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
});