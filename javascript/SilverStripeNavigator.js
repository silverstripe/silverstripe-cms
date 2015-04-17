function windowName(suffix) {
	var base = document.getElementsByTagName('base')[0].href.replace('http://','').replace(/\//g,'_').replace(/\./g,'_');
	return base + suffix;
}

(function($) {
	$(document).ready(function() {
		$('#switchView a.newWindow').on('click', function(e) {
			var w = window.open(this.href, windowName(this.target));
			w.focus();
			return false;
		});

		$('#SilverStripeNavigatorLink').on('click', function(e) {
			$('#SilverStripeNavigatorLinkPopup').toggle();
			return false;
		});
		
		$('#SilverStripeNavigatorLinkPopup a.close').on('click', function(e) {
			$('#SilverStripeNavigatorLinkPopup').hide();
			return false;
		});
		
		$('#SilverStripeNavigatorLinkPopup input').on('focus',function(e) {
			this.select();
		});
	});

})(jQuery);
