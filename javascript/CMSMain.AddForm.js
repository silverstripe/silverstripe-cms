(function($) {
	$.entwine('ss', function($){
		/**
		 * Reset the parent node selection if the type is
		 * set back to "toplevel page", to avoid submitting inconsistent state.
		 */
		$(".cms-add-form .parent-mode :input").entwine({
			onclick: function(e) {
				if(this.val() == 'top') {
					var parentField = this.closest('form').find('#ParentID .TreeDropdownField')
					parentField.setValue('');
					parentField.setTitle('');
				}
			}
		});
		
		$(".cms-add-form").entwine({
			onadd: function() {
				var self = this;
				this.find('#ParentID .TreeDropdownField').bind('change', function() {
					self.updateTypeList();
				});
				this.updateTypeList();
			},
			
			/**
			 * Limit page type selection based on parent class.
			 * Similar implementation to LeftAndMain.Tree.js.
			 */
			updateTypeList: function() {
				var hints = this.find('.hints').data('hints'), 
					metadata = this.find('#ParentID .TreeDropdownField').data('metadata'),
					id = this.find('#ParentID .TreeDropdownField').getValue(),
					newClassName = (id && metadata) ? metadata.ClassName : null,
					hintKey = (newClassName) ? newClassName : 'Root',
					hint = (typeof hints[hintKey] != 'undefined') ? hints[hintKey] : null,
					allAllowed = true;
				
				var disallowedChildren = (hint && typeof hint.disallowedChildren != 'undefined') ? hint.disallowedChildren : [],
					defaultChildClass = (hint && typeof hint.defaultChild != 'undefined') ? hint.defaultChild : null;
				
				// Limit selection
				this.find('#PageType li').each(function() {
					var className = $(this).find('input').val(), 
						isAllowed = ($.inArray(className, disallowedChildren) == -1);
					
					$(this).setEnabled(isAllowed);
					if(!isAllowed) $(this).setSelected(false);
					allAllowed = allAllowed && isAllowed;
				});
				
				// Set default child selection, or fall back to first available option
				if(defaultChildClass) {
					var selectedEl = this.find('#PageType li input[value=' + defaultChildClass + ']').parents('li:first');
				} else {
					var selectedEl = this.find('#PageType li:not(.disabled):first');
				}
				selectedEl.setSelected(true);
				selectedEl.siblings().setSelected(false);

				// Disable the "Create" button if none of the pagetypes are available
				var buttonState = (this.find('#PageType li:not(.disabled)').length) ? 'enable' : 'disable';
				this.find('button[name=action_doAdd]').button(buttonState);

				this.find('.message-restricted')[allAllowed ? 'hide' : 'show']();
			}
		});
		
		$(".cms-add-form #PageType li").entwine({
			onclick: function(e) {
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
				var tree = $('.cms-tree'), list = $('.cms-list'), parentId = 0;

				// Choose parent ID either from tree or list view, depending which is visible
				if(tree.is(':visible')) {
					var selected = tree.jstree('get_selected');
					parentId = selected ? $(selected[0]).data('id') : null;
				} else {
					var state = list.find('input[name="Page[GridState]"]').val();
					if(state) parentId = parseInt(JSON.parse(state).ParentID, 10);
				}
					
				var data = {selector: this.data('targetPanel'),pjax: this.data('pjax')}, url;
				if(parentId) {
					extraParams = this.data('extraParams') ? this.data('extraParams') : '';
					url = $.path.addSearchParams(ss.i18n.sprintf(this.data('urlAddpage'), parentId), extraParams);
				} else {
					url = this.attr('href');
				}

				$('.cms-container').loadPanel(url, null, data);
				e.preventDefault();

				// Remove focussed state from button
				this.blur();

				// $('.cms-page-add-form-dialog').dialog('open');
				// e.preventDefault();
			}
		});
	});
}(jQuery));
