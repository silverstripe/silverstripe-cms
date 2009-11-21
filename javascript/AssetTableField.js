(function($) {
	$.concrete('ss', function($){
		
		$('.AssetTableField').concrete({
			onmatch: function() {
				var self = this;
				
				// search button
				this.find('input#FileFilterButton').click(function(e) {
					var btn = $(this);
					$(this).addClass('loading');
					self.refresh(function() {btn.removeClass('loading');});
					return false;
				});
				
				// clear button
				this.find('input#FileFilterClearButton').click(function(e) {
					self.find('input#FileSearch').val('');
					self.find('input#FileFilterButton').click();
					return false;
				});
				
				// search field
				this.find('input#FileSearch').keypress(function(e) {
					if(e.keyCode == $.ui.keyCode.ENTER) {
						self.find('input#FileFilterButton').click();
					}
				});
				
				this._super();
			},
			
			refresh: function(callback) {
				var self = this;
				this.load(
					this.attr('href'),
					this.find(':input').serialize(),
					function(response, status, xmlhttp) {
						Behaviour.apply(self[0], true);
						if(callback) callback.apply(arguments);
					}
				);
			}
		});
		
		/**
		 * Checkboxes used to batch delete files
		 */
		$('.AssetTableField :checkbox').concrete({
			onchange: function() {
				var container = this.parents('.AssetTableField');
				var input = container.find('input#deletemarked');
				if(container.find(':input[name=Files\[\]]:checked').length) {
					input.removeAttr('disabled');
				} else {
					input.attr('disabled', 'disabled');
				}
			}
		})
		
		/**
		 * Batch delete files marked by checkboxes in the table.
		 * Refreshes the form field afterwards via ajax.
		 */
		$('.AssetTableField input#deletemarked').concrete({
			onmatch: function() {
				this.attr('disabled', 'disabled');
				this._super();
			},
			onclick: function(e) {
				if(!confirm(ss.i18n._t('AssetTableField.REALLYDELETE'))) return false;
				
				var container = this.parents('.AssetTableField');
				var self = this;
				this.addClass('loading');
				$.post(
					container.attr('href') + '/deletemarked',
					this.parents('form').serialize(),
					function(data, status) {
						self.removeClass('loading');
						container.refresh();
					}
				);
				return false;
			}
		});
	});
}(jQuery));