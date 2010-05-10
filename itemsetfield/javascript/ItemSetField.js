(function($){

$('.item-set-field ul').livequery(
	function(){ $(this).sortable() }
);

var dialog_for = function(el) {
	/* If we're in an item-set-field, we want a special dialog just for us */
	var field = el.parents('.item-set-field').eq(0);
	if (field.length) {
		var dialog = field.data('item-set-dialog');
		
		if (!dialog) {
			dialog = $("<div class='item-set-dialog'></div>").appendTo('body');
			dialog.dialog({autoOpen: false, modal: true, width: 400, height: 600, draggable: false, resizable: false});
			field.data('item-set-dialog', dialog);
		}
		
		return dialog;
	}
		
	/* Otherwise, assume we're in a dialog all ready */
	return el.parents('.item-set-dialog').eq(0);
}

var request = function(eventel, ajax){
	$.ajax($.extend({}, ajax, {
		dataType: 'html',
		success: function(data){
			var holder = $('<div></div>'); holder.html(data); var el = holder.children();
			
			// If we get an item-set-field back
			if (el.length == 1 && el.is('.item-set-field')) {
				// Find that particular field
				var field = $('#'+el.attr('id'));
				
				// Close any associated dialog (but not all dialogs - this item-set-field might live in a dialog)
				var dialog = field.data('item-set-dialog');
				if (dialog) dialog.dialog('close');
				
				// And replace that item-set-field with the new contents.
				field.replaceWith(el);
			}
			
			else {
				dialog_for(eventel).empty().append(el).dialog('open');
			}
		},
		error: function(){
			statusMessage('Couldnt execute action', 'bad');
		}
	}));
}

$('.item-set-field .item-set-field-action, .item-set-dialog .item-set-field-action').live('click', function(e){
	e.preventDefault();
	
	var action = $(this);
	request(action, { url: action.attr('href') || action.attr('rel') });
})

$('.item-set-field form, .item-set-dialog form').livequery('submit', function(e){
	e.preventDefault();
	
	var form = $(this);
	request(form, {
		data: form.serialize(),
		url: form.attr('action'),
		type: form.attr('method') || 'get'
	});
});


})(jQuery);
