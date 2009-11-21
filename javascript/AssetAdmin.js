/**
 * Configuration for the left hand tree
 */
if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/assets/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/assets/ajaxupdatesort';
SiteTreeHandlers.loadPage_url = 'admin/assets/getitem';
SiteTreeHandlers.loadTree_url = 'admin/assets/getsubtree';
SiteTreeHandlers.showRecord_url = 'admin/assets/show/';
SiteTreeHandlers.controller_url = 'admin/assets';

var _HANDLER_FORMS = {
	addpage : 'Form_AddForm',
	deletepage : 'Form_DeleteItemsForm',
	sortitems : 'sortitems_options'
};

(function($) {
	/**
	 * Delete selected folders through "batch actions" tab.
	 */
	$(document).ready(function() {
		$('#Form_BatchActionsForm').concrete('ss').register(
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
	
	$.concrete('ss', function($){
		$('#Form_SyncForm').concrete({
			onsubmit: function(e) {
				var button = jQuery(this).find(':submit:first');
				button.addClass('loading');
				$.get(
					jQuery(this).attr('action'),
					function() {
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
				);
				
				return false;
			}
		});
	});
}(jQuery));