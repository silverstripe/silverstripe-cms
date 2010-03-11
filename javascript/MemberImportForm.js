(function($) {
$(document).ready(function() {
	$('.import-form .advanced').hide();
	
	$('.import-form a.toggle-advanced').live(
		'click', 
		function(e) {
			$(this).parents('form:eq(0)').find('.advanced').toggle();
			return false;
		}
	);
});
}(jQuery));