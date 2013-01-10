(function($) {
	$.entwine('ss', function($) {
		/**
		 * Class: .field.urlsegment
		 *
		 * Provides enhanced functionality (read-only/edit switch) and
		 * input validation on the URLSegment field
		 */
		$('.field.urlsegment').entwine({

			/**
			 * Constructor: onmatch
			 */
			onmatch : function() {
				this._addActions(); // add elements and actions for editing
				this.edit(); // toggle
				this._autoInputWidth(); // set width of input field

				this._super();
			},

			onunmatch: function() {
				this._super();
			},

			/**
			 * Function: edit
			 *
			 * Toggles the edit state of the field
			 *
			 * Return URLSegemnt val()
			 *
			 * Parameters:
			 *  (Bool) auto (optional, triggers a second toggle)
			 */
			edit: function(auto) {

				var field = this.getInputField(),
					holder = this.find('.preview'),
					edit = this.find('.edit'),
					update = this.find('.update'),
					cancel = this.find('.cancel'),
					help = this.find('.help');

				// transfer current value to holder
				holder.html(this.constructLink());

				// toggle elements
				if (field.is(':visible')) {
					update.hide();
					cancel.hide();
					field.hide();
					holder.show();
					edit.show();
					help.hide();
				}
				else {
					//retain current value for cancel
					edit.attr("rel", field.val()),
					edit.hide();
					holder.hide();
					field.show();
					update.show();
					cancel.show();
					help.show();
				}
				// field updated from another fields value
				// reset to original state
				if (auto) this.edit();

				return field.val();
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
					holder = this.find('.preview'),
					currentVal = holder.text(),
					updateVal,
					title = arguments[0];

				if (title && title !== "") {
					updateVal = title;
				} else {
					updateVal = field.val();
				}

				if (currentVal != updateVal) {
					self.suggest(updateVal, function(data) {
						var newVal = decodeURIComponent(data.value);
						field.val(newVal);
						self.edit(title);
					});
				} else {
					self.edit();
				}
			},

			/**
			 * Function: cancel
			 *
			 * Cancels any changes to the field
			 *
			 * Return URLSegemnt val()
			 *
			 */
			cancel: function() {
				var field = this.getInputField();
				//return to last value
				//toggle
				field.val(this.find('.edit').attr("rel"));
				this.edit();
				return field.val();
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
				if(urlParts.search) url += '&' + urlParts.search.replace(/^\?/, '');

				$.get(
					url,
					function(data) {callback.apply(this, arguments);}
				);

			},

			/**
			 * Function: _addActions
			 *
			 * Utility to add edit buttons and actions
			 *
			 */
			_addActions: function() {
				var self = this,
					field = this.find(':text'),
					prefix = this.find('.prefix'),
					preview,
					editAction,
					updateAction,
					cancelAction;

				// element to display non-editable text
				preview = $('<span />', {
					'class': 'preview'
				});

				// edit button
				editAction = $('<button />', {
					'class': 'ss-ui-button ss-ui-button-small edit',
					'text': 'Edit',
					'click': function(e) {
						e.preventDefault();
						self.edit();
						self.find(':text').focus();
					}
				});

				// update button
				updateAction = $('<button />', {
					'class': 'update ss-ui-button-small',
					'text': 'OK',
					'click': function(e) {
						e.preventDefault();
						self.update();
					}
				});

				// cancel button
				cancelAction = $('<button />', {
					'class': 'cancel ss-ui-action-minor ss-ui-button-small',
					'href': '#',
					'text': 'cancel',
					'click': function(e) {
						e.preventDefault();
						self.cancel();
					}
				});

				// insert elements
				preview.insertAfter(prefix);
				prefix.hide();
				editAction.insertAfter(field);
				cancelAction.insertAfter(field);
				updateAction.insertAfter(field);
			},

			/**
			 * Function: _autoInputWidth
			 *
			 * Sets the width of input so it lines up with the other fields
			 */
			_autoInputWidth: function() {
				var field = this.getInputField();
				field.width((field.width() + 15) - this.find('.prefix').width());
			},

			/**
			 * Function: getInputField
			 *
			 * We use this function to replace all of the individual calls to find the input field.
			 * In this way, it is easier to change and to cache the value.
			 *
			 * @return Object
			 */
			getInputField: function() {
				return this.find(':text');
			},

			/**
			 * Function: constructLink
			 *
			 * Returns an object like this <a href="PrefixLink + URLSement" target="_blank">ShortenedFullURL</a>
			 * @return Object
			 */
			constructLink: function() {
				var link = $('<a />', {
					'href': $(".prefix").attr("href")+this.getInputField().val(),
					//we add one space to accentuate the URLSegment
					'text': $(".prefix").text()+" "+this.getInputField().val(),
					'target': '_blank'
				});
				return link;
			}
		});
	});
}(jQuery));
