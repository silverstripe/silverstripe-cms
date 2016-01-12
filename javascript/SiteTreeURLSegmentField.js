(function($) {
	$.entwine('ss', function($) {
		/**
		 * Class: .field.urlsegment
		 *
		 * Provides enhanced functionality (read-only/edit switch) and
		 * input validation on the URLSegment field
		 */
		$('.field.urlsegment:not(.readonly)').entwine({
	
			// Roughly matches the field width including edit button
			MaxPreviewLength: 55,

			Ellipsis: '...',

			onmatch : function() {
				// Only initialize the field if it contains an editable field.
				// This ensures we don't get bogus previews on readonly fields.
				if(this.find(':text').length) this.toggleEdit(false);
				this.redraw();
				
				this._super();
			},

			redraw: function() {
				var field = this.find(':text'),
					url = decodeURI(field.data('prefix') + field.val()),
					previewUrl = url;

				// Truncate URL if required (ignoring the suffix, retaining the full value)
				if(url.length > this.getMaxPreviewLength()) {
					previewUrl = this.getEllipsis() + url.substr(url.length - this.getMaxPreviewLength(), url.length);
				}

				// Transfer current value to holder
				this.find('.preview').attr('href', encodeURI(url + field.data('suffix'))).text(previewUrl);
			},

			/**
			 * @param Boolean
			 */
			toggleEdit: function(toggle) {
				var field = this.find(':text');

				this.find('.preview-holder')[toggle ? 'hide' : 'show']();
				this.find('.edit-holder')[toggle ? 'show' : 'hide']();

				if(toggle) {
					field.data("origval", field.val()); //retain current value for cancel
					field.focus();
				}
			},
			
			/**
			 * Commits the change of the URLSegment to the field
			 * Optional: pass in (String) to update the URLSegment
			 */
			update: function() {
				var self = this,
					field = this.find(':text'),
					currentVal = field.data('origval'),
					title = arguments[0],
					updateVal = (title && title !== "") ? title : field.val();
				
				if (currentVal != updateVal) {
					this.addClass('loading');
					this.suggest(updateVal, function(data) {
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
			
			/**
			 * Cancels any changes to the field
			 */
			cancel: function() {
				var field = this.find(':text');
				field.val(field.data("origval"));
				this.toggleEdit(false);
			},
	
			/**
			 * Return a value matching the criteria.
			 *
			 * @param (String)
			 * @param (Function)
			 */
			suggest: function(val, callback) {
				var self = this,
					field = self.find(':text'),
					urlParts = $.path.parseUrl(self.closest('form').attr('action')),
					url = urlParts.hrefNoSearch + '/field/' + field.attr('name') + '/suggest/?value=' + encodeURIComponent(val);
				if(urlParts.search) url += '&' + urlParts.search.replace(/^\?/, '');

				$.ajax({
					url: url,
					success: function(data) {
						callback.apply(this, arguments);
					},
					error: function(xhr, status) {
						xhr.statusText = xhr.responseText;
					},
					complete: function() {
						self.removeClass('loading');
					}
				});
			}
		});

		$('.field.urlsegment .edit').entwine({
			onclick: function(e) {
				e.preventDefault();
				this.closest('.field').toggleEdit(true);
			}
		});

		$('.field.urlsegment .update').entwine({
			onclick: function(e) {
				e.preventDefault();
				this.closest('.field').update();
			}
		});

		$('.field.urlsegment .cancel').entwine({
			onclick: function(e) {
				e.preventDefault();
				this.closest('.field').cancel();
			}
		});
	});

}(jQuery));
