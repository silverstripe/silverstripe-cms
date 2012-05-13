(function($) {
	$.entwine('ss', function($){
		$('#Form_EditForm_RedirectionType input').entwine({
			onmatch: function() {
				var self = $(this);
				if(self.attr('checked')) this.toggle();
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			onclick: function() {
				this.toggle();
			},
			toggle: function() {
				if($(this).attr('value') == 'Internal') {
					$('#ExternalURL').hide();
					$('#LinkToID').show();
				} else {
					$('#ExternalURL').show();
					$('#LinkToID').hide();
				}
			}
		});
	});
})(jQuery);

