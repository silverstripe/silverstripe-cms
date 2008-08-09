jQuery(document).ready(function() {

	function showRecord(uri) {
		jQuery.get(uri, function(result){
			jQuery('#right').html(result);
			Behaviour.apply();
		});
	}
	
	function saveRecord(uri, data) {
		//jQuery.post(uri, data, function(result) {)
	}
	
	jQuery('#AddForm_holder form').submit(function(){
		className = jQuery('select option:selected', this).val();
		// @todo remove dependency on hardcoded URL path
		requestPath = jQuery(this).attr('action') + '/add/' + className;
		showRecord(requestPath);
		return false;
	});
	
	// attach generic action handler to all forms displayed in the #right panel
	jQuery('#right .action').click(function(){
		alert('do something here');
		return false;
	});
	
	jQuery('#SearchForm_holder').tabs();
	
	jQuery('.tab form').submit(function(){
		form = jQuery(this);
		
		var data = {};
		jQuery('*[name]', form).each(function(){
			var t = jQuery(this);
			var val = (t.attr('type') == 'checkbox') ? (t.attr('checked') == true) ? 1 : 0 : t.val();
			data[t.attr('name')] = val;
		});

		jQuery.get(form.attr('action'), data, function(result){
			jQuery('#ResultTable_holder').html(result);
			jQuery('#ResultTable_holder td').click(function(){
				td = jQuery(this);
				showRecord(td.parent().attr('id'));
				td.parent().parent().find('td').removeClass('active');
				td.addClass('active').siblings().addClass('active');
			}).hover(function(){
						jQuery(this).addClass('over').siblings().addClass('over')
					}, function(){
						jQuery(this).removeClass('over').siblings().removeClass('over')
					});
		});
		
		return false;
	});
	
});