/**
 * File: AssetAdmin.js
 */

(function($) {
	/**
	 * Delete selected folders through "batch actions" tab.
	 */
	$(document).ready(function() {
		$('#Form_BatchActionsForm').entwine('ss').register(
			// TODO Hardcoding of base URL
			'admin/assets/batchactions/delete', 
			function(ids) {
				var confirmed = confirm(
					ss.i18n.sprintf(
						ss.i18n._t('AssetAdmin.BATCHACTIONSDELETECONFIRM'),
						ids.length
					)
				);
				return (confirmed) ? ids : false;
			}
		);
	});
	
	$.entwine('ss', function($){
		
		/**
		 * Class: #Form_SyncForm
		 */
		$('#Form_SyncForm').entwine({
			
			/**
			 * Function: onsubmit
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			onsubmit: function(e) {
				var button = jQuery(this).find(':submit:first');
				button.addClass('loading');
				$.ajax({
					url: jQuery(this).attr('action'),
					data: this.serializeArray(),
					success: function() {
						button.removeClass('loading');
						// reload current form and tree
						var currNode = $('#sitetree')[0].firstSelected();
						if(currNode) {
						  var url = $(currNode).find('a').attr('href');
        			$('#Form_EditForm').loadForm(url);
						}
						$('#sitetree')[0].setCustomURL('admin/assets/getsubtree');
						$('#sitetree')[0].reload({onSuccess: function() {
							// TODO Reset current tree node
						}});
					}
				});
				
				return false;
			}
		});
	});
}(jQuery));