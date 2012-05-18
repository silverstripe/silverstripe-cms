/**
 * File: ReportAdmin.Tree.js
 */
(function($) {
	$.entwine('ss.tree', function($){
		/**
		 * Class: .cms-tree
		 * 
		 * Tree panel.
		 */
		$('.cms-tree').entwine({
			onmatch: function() {
				// make sure current ID of loaded form is actually selected in tree
				var id = $('.cms-edit-form :input[name=ID]').val();
				if (id) this[0].setCurrentByIdx(id);
				
				this._super();
			},
			onunmatch: function() {
				this._super();
			}
		});
	});
}(jQuery));
