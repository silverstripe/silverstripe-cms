jQuery(function($){
	$('.ss-gridfield').entwine({
		onopennewview: function(e, url) {
			$('.cms-container').entwine('ss').loadPanel(url);
			return false;
		},

		onopeneditview: function(e, url) {
			$('.cms-container').entwine('ss').loadPanel(url);
			return false;
		}
	});
});