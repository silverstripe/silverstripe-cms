(function($) {
	$.entwine('ss', function($) {

		/**
		 * Class: .field.urlsegment
		 *
		 * Input validation on the URLSegment field
		 */
		$('.field.urlsegment:not(.readonly)').entwine({

			/**
			 * URLSegment Input Field
			 */
			InputField: null,

			/**
			 * URLSegment Preview Element
			 */
			Preview: null,

			/**
			 * URL Prefix Element
			 */
			Prefix: null,

			/**
			 * Edit Button Element
			 */
			EditButton: null,

			/**
			 * Update Button Element
			 */
			UpdateButton: null,

			/**
			 * Cancel Button Element
			 */
			CancelButton: null,

			/**
			 * Help Text Element
			 */
			Help: null,

			/**
			 * Ex. Title Field
			 */
			RelatedField: null,

			/**
			 * Constructor: onmatch
			 */
			onmatch : function() {
				var self = this,
					relatedFieldByName,
					relatedField;

				// Only initialize the field if it contains an editable field.
				// This ensures we don't get bogus previews on readonly fields.
				if(!this.find(':text').length) {
					this._super();
					return;
				}

				// set elements
				this.setInputField(this.find(':text'));
				this.setPreview(this.find('.preview'));
				this.setPrefix(this.find('.prefix'));
				this.setEditButton(this.find('.edit'));
				this.setUpdateButton(this.find('.update'));
				this.setCancelButton(this.find('.cancel'));
				this.setHelp(this.find('.help'));

				// edit button
				this.on('click', '.edit', function(e) {
					e.preventDefault();
					self.edit();
					self.find(':text').focus();
				});

				// update button
				this.on('click', '.update', function(e) {
					e.preventDefault();
					self.update();
				});

				// cancel button
				this.on('click', '.cancel', function(e) {
					e.preventDefault();
					self.cancel();
				});

				// default state
				this.reset();

				// field has been transformed
				this.addClass('urlsegment-transformed');

				// check for a related field
				relatedFieldByName = this.getInputField().data('related-field');
				if (relatedFieldByName) {
					relatedField = this.parents('form').find('input[name=' + relatedFieldByName + ']');
					if (relatedField.length) {
						this.setRelatedField(relatedField);
						this._addUpdateFromBehavior();
					}
				}

				this._super();
			},
			onunmatch: function() {
				this._super();
			},

			/**
			 * Function: reset
			 */
			reset: function() {
				var field = this.getInputField();

				field.hide();
				this.getUpdateButton().hide();
				this.getCancelButton().hide();
				this.getPreview().show().text(field.val());
				this.getEditButton().show();
				this.getHelp().slideUp();
				this._autoInputWidth();
			},

			/**
			 * Function: edit
			 */
			edit: function() {
				var field = this.getInputField();

				// transfer current value to preview
				// we can restore from here later
				// if action is canceled
				this.getPreview().text(field.val());

				if (!field.is(':visible')) {
					field.show();
					this.getUpdateButton().show();
					this.getCancelButton().show();
					this.getPreview().hide();
					this.getEditButton().hide();
					this.getHelp().slideDown();
				} else {
					this.reset();
				}
			},

			/**
			 * Function: update
			 *
			 * Commits the change of the URLSegment to the field
			 * Optional: pass in (String)
			 * to update the URLSegment
			 */
			update: function() {

				var self = this,
					field = this.getInputField(),
					holder = this.getPreview(),
					currentVal = holder.text(),
					updateVal,
					title = arguments[0];

				if (title && title !== "") {
					updateVal = title;
				} else {
					updateVal = field.val();
				}

				if (currentVal != updateVal) {
					this.addClass('loading');
					this.suggest(updateVal, function(data) {
						var newVal = decodeURIComponent(data.value);
						field.val(newVal);
						self.reset();
						self.removeClass('loading');
					});
				} else {
					this.reset();
				}
			},

			/**
			 * Function: cancel
			 *
			 * Cancels any changes to the field
			 *
			 */
			cancel: function() {
				var field = this.getInputField(),
					holder = this.getPreview();

					field.val(holder.text());
					this.reset();
			},

			/**
			 * Function: suggest
			 *
			 * Return a value matching the criteria.
			 *
			 * Parameters:
			 *  (String) val
			 *  (Function) callback
			 */
			suggest: function(val, callback) {
				var field = this.getInputField(),
					urlParts = $.path.parseUrl(this.closest('form').attr('action')),
					url = urlParts.hrefNoSearch + '/field/' + field.attr('name') + '/suggest/?value=' + encodeURIComponent(val);

				if (urlParts.search) url += '&' + urlParts.search.replace(/^\?/, '');

				$.get(url, function(data) {
					callback.apply(this, arguments);
				});
			},

			/**
			 * Function: _addUpdateFromBehavior
			 *
			 * Adds "update from" behaviour to related field
			 */
			_addUpdateFromBehavior: function() {
				var self = this,
					urlsegment = this.getInputField(),
					updateURLSegment,
					related = this.getRelatedField(),
					livelink = $('input[name=LiveLink]', this.parents('form'));

				updateURLSegment = $('<button />', {
					'class': 'update ss-ui-button-small',
					'text': ss.i18n._t('URLSEGMENT.UpdateURLSegment', 'Update URL'),
					'click': function(e) {
						e.preventDefault();
						self.update(related.val());
						$(this).fadeOut();
					}
				});

				// insert elements
				updateURLSegment.insertAfter(related);
				updateURLSegment.hide();

				// watch for changes on related field
				related.bind('change', function(e) {
					var field = $(this);

					// Criteria for defining a "new" page
					if ((urlsegment.val().indexOf('new') === 0) && livelink.val() === '') {
						self.update(related.val());
					} else {
						$('.update', field.parent()).fadeIn();
					}
				});

			},

			/**
			 * Function: _autoInputWidth
			 *
			 * Adjust width of text input
			 */
			_autoInputWidth: function() {
				var field = this.getInputField();
				field.attr('size', field.val().length + 4);
			}
		});
	});
}(jQuery));
