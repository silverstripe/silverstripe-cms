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
		
		$(".cms-add-form").entwine({
			onmatch: function() {
				var self = this;
				this.find('#ParentID .TreeDropdownField').bind('change', function() {
					self.updateTypeList();
				});
			},
			
			/**
			 * Limit page type selection based on parent class.
			 * Similar implementation to LeftAndMain.Tree.js.
			 */
			updateTypeList: function() {
				var hints = this.find('.hints').data('hints'), 
					metadata = this.find('#ParentID .TreeDropdownField').data('metadata'),
					id = this.find('#ParentID .TreeDropdownField').getValue(),
					newClassName = metadata.ClassName,
					disallowedChildren = hints[newClassName ? newClassName : 'Root'].disallowedChildren || [],
					defaultChildClass = hints[newClassName ? newClassName : 'Root'].defaultChild || null;
				
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
			setSelected: function(bool) {
				this.toggleClass('selected', bool);
			},
			setEnabled: function(bool) {
				$(this).toggleClass('disabled', bool);
				$(this).find('input').attr('disabled', bool ? '' : 'disabled');
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