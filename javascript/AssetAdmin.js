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
	$('.AssetAdmin').concrete('ss', function($) {
		return {
			onmatch: function() {
				
				this._super();
			}
		};
	});
	
	/**
	 * Delete selected folders through "batch actions" tab.
	 */
	$('#Form_BatchActionsForm').concrete('ss').register(
		'admin/assets/batchactions/delete', 
		function(ids) {
			var confirmed = confirm(
				ss.i18n.sprintf(
					ss.i18n._t(
						'AssetAdmin.BATCHACTIONSDELETECONFIRM',
						"Do you really want to delete %d folders?"
					),
					ids.length
				)
			);
			return (confirmed) ? ids : false;
		}
	);
	
	$('#Form_SyncForm').concrete('ss', function($) {
		return {
			onmatch: function() {
				this.bind('submit', this._onsubmit);			
				this._super();
			},
			_onsubmit: function(e) {
				var button = jQuery(this).find(':submit:first');
				button.addClass('loading');
				$.get(
					jQuery(this).attr('action'),
					function() {
						button.removeClass('loading');
					}
				);
				
				return false;
			}
		};
	});
}(jQuery));