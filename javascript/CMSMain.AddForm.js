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
				this.parents('li:first').addClass('selected').siblings().removeClass('selected');
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