(function($) {
	$.entwine('ss', function($){
		$('.cms-page-add-form-dialog').entwine({
			onmatch: function() {
				this.dialog({
					autoOpen: false,
					bgiframe: true,
					modal: true,
					height: 400,
					width: 600,
					ghost: true
				});
				this._super();
			}
		});
	
		$('.cms-page-add-form-dialog input[name=PageType]').entwine({
			onmatch: function() {
				if(this.is(':checked')) this.trigger('click');
				this._super();
			},
			onclick: function() {
				var el = this.parents('li:first');
				el.setSelected(true);
				el.siblings().setSelected(false);
			}
		});

		/**
		 * Reset the parent node selection if the type is
		 * set back to "toplevel page", to avoid submitting inconsistent state.
		 */
		$(".cms-add-form .parent-mode :input").entwine({
			onclick: function(e) {
				if(this.val() == 'top') {
					var parentField = this.closest('form').find('#ParentID .TreeDropdownField');
					parentField.setValue('');
				}
			}
		});
		
		$(".cms-add-form").entwine({
			onmatch: function() {
				var self = this;
				this.find('#ParentID .TreeDropdownField').bind('change', function() {
					self.updateTypeList();
				});
				this.updateTypeList();
				this._super();
			},
			
			/**
			 * Limit page type selection based on parent class.
			 * Similar implementation to LeftAndMain.Tree.js.
			 */
			updateTypeList: function() {
				var hints = this.find('.hints').data('hints'), 
					metadata = this.find('#ParentID .TreeDropdownField').data('metadata'),
					id = this.find('#ParentID .TreeDropdownField').getValue(),
					newClassName = metadata ? metadata.ClassName : null,
					hintKey = newClassName ? newClassName : 'Root',
					hint = (typeof hints[hintKey] != 'undefined') ? hints[hintKey] : null;
				
				var disallowedChildren = (hint && typeof hint.disallowedChildren != 'undefined') ? hint.disallowedChildren : [],
					defaultChildClass = (hint && typeof hint.defaultChild != 'undefined') ? hint.defaultChild : null;
				
				// Limit selection
				this.find('#PageType li').each(function() {
					var className = $(this).find('input').val(), isAllowed = ($.inArray(className, disallowedChildren) == -1);
					$(this).setEnabled(isAllowed);
				});
				
				// Set default child selection, or fall back to first available option
				if(defaultChildClass) {
					var selectedEl = this.find('#PageType li input[value=' + defaultChildClass + ']').parents('li:first');
				} else {
					var selectedEl = this.find('#PageType li:not(.disabled):first');
				}
				selectedEl.setSelected(true);
				selectedEl.siblings().setSelected(false);
			}
		});
		
		$(".cms-add-form #PageType li").entwine({
			onclick: function() {
				this.setSelected(true);
			},
			setSelected: function(bool) {
				var input = this.find('input');
				this.toggleClass('selected', bool);
				if(bool && !input.is(':disabled')) {
					this.siblings().setSelected(false);
					input.attr('checked', 'checked');
				}
			},
			setEnabled: function(bool) {
				$(this).toggleClass('disabled', !bool);
				if(!bool) $(this).find('input').attr('disabled',  'disabled').removeAttr('checked');
				else $(this).find('input').removeAttr('disabled');
			}
		});
	
		$(".cms-page-add-button").entwine({
			onclick: function(e) {
				$('.cms-page-add-form-dialog').dialog('open');
				e.preventDefault();
			}
		});
	});
}(jQuery));
