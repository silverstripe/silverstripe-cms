/**
 * File: ReportAdmin.Tree.js
 */
(function($) {
	$.entwine('ss', function($){
		/**
		 * Class: #sitetree
		 * 
		 * Tree panel.
		 */
		$('#sitetree').entwine({
			onmatch: function() {
				// make sure current ID of loaded form is actually selected in tree
				var id = $('#Form_EditForm :input[name=ID]').val();
				if (id) this[0].setCurrentByIdx(id);
				
				this._super();
			}
		});
	});
}(jQuery));