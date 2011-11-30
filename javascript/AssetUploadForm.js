(function($) {

	$.entwine('ss', function($){
		
		$('form.asset-upload').entwine({
			onmatch: function() {
				// TODO Add drop area
				// TODO Hook into upload status changes to retrieve file ID and invoke addFileToList()
				this._super();
			},
			/**
			 * Triggered when upload starts or finishes
			 */
			addFileToList: function(id, name) {
				var fileEl = $('<li class="asset-upload-file" data-id="' + id + '">' + name + '</li>');
				this.find('.asset-upload-files').append(fileEl);
				fileEl.load(this.attr('action') + '/viewfieldlistforfile/?ID=' + id);
				
			}
		});
		
		$('form.asset-upload .asset-upload-file').entwine({
			onmatch: function() {
				// Hide all fields by default
				// this.find('.asset-upload-file-fields').hide();
				this._super();
			},
			onclick: function() {
				this.find('.asset-upload-file-fields').show();
			}
			
		});
	});
}(jQuery));