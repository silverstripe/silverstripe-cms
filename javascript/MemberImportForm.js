(function($) {
	$.entwine('ss', function($){
		$('.import-form .advanced').entwine({
			onmatch: function() {
				this._super();
				
				this.hide();
			}
		});
		
		$('.import-form a.toggle-advanced').entwine({
			onclick: function(e) {
				this.parents('form:eq(0)').find('.advanced').toggle();
				return false;
			}
		});
	});
	
}(jQuery));