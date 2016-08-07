/**
 * File: ReportAdmin.js
 */

(function($) {
	$.entwine('ss', function($){
		$('.ReportAdmin .cms-edit-form').entwine({
			onsubmit: function(e) {
				var url = window.location.href.split('?')[0], params = this.find(':input[name^=filters]').serializeArray();
				params = $.grep(params, function(param) {return (param.value);}); // filter out empty
				if(params) url = $.path.addSearchParams(url, params);
				$('.cms-container').loadPanel(url);
				return false;
			}
		});
	
	});
})(jQuery);
