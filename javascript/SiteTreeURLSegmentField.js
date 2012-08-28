(function($) {
	$.entwine('ss', function($) {
		/**
		 * Class: .field.urlsegment
		 * 
		 * Input validation on the URLSegment field
		 */
		$('.field.urlsegment').entwine({
	
			/**
			 * Constructor: onmatch
			 */
			onmatch : function() {
				// Only initialize the field if it contains an editable field.
				// This ensures we don't get bogus previews on readonly fields.
				if(this.find(':text').length) {
					this._addActions(); // add elements and actions for editing
					this.edit(); // toggle
					this._autoInputWidth(); // set width of input field
				}
				
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
				
				var field = this.find(':text'),
					holder = this.find('.preview'),
					edit = this.find('.edit'),
					update = this.find('.update'),
					cancel = this.find('.cancel'),
					help = this.find('.help');
				
				// transfer current value to holder
				holder.text(field.val());
				
				// toggle elements
				if (field.is(':visible')) {
					update.hide();
					cancel.hide();
					field.hide();
					holder.show();
					edit.show();
					help.hide();
				} else {
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
					field = this.find(':text'),
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
					self.addClass('loading');
					self.suggest(updateVal, function(data) {
						var newVal = decodeURIComponent(data.value);
						field.val(newVal);
						self.edit(title);
						self.removeClass('loading');
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
				var field = this.find(':text'),
					holder = this.find('.preview');
					field.val(holder.text());
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
				var field = this.find(':text'), urlParts = $.path.parseUrl(this.closest('form').attr('action')),
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
					'text': ss.i18n._t('URLSEGMENT.Edit'),
					'click': function(e) {
						e.preventDefault();
						self.edit();
						self.find(':text').focus();
					}
				});
				
				// update button
				updateAction = $('<button />', {
					'class': 'update ss-ui-button-small',
					'text': ss.i18n._t('URLSEGMENT.OK'),
					'click': function(e) {
						e.preventDefault();
						self.update();
					}
				});
				
				// cancel button
				cancelAction = $('<button />', {
					'class': 'cancel ss-ui-action-minor ss-ui-button-small',
					'href': '#',
					'text':  ss.i18n._t('URLSEGMENT.Cancel'),
					'click': function(e) {
						e.preventDefault();
						self.cancel();
					}
				});
				
				// insert elements
				preview.insertAfter('.prefix');
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
				var field = this.find(':text');
				field.width((field.width() + 15) - this.find('.prefix').width());
			}
		});
	});
}(jQuery));
