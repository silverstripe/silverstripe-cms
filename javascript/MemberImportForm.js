(function($) {
	$.concrete('ss', function($){
		$('.import-form .advanced').concrete({
			onmatch: function() {
				this._super();
				
				this.hide();
			}
		});
		
		$('.import-form a.toggle-advanced').concrete({
			onclick: function(e) {
				this.parents('form:eq(0)').find('.advanced').toggle();
				return false;
			}
		});
	});
	
}(jQuery));