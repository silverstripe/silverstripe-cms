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
		 * Load folder detail view via controller methods
		 * rather than built-in GridField view (which is only geared towards showing files).
		 */
		$('#Form_EditForm_File .ss-gridfield-item').entwine({
			onclick: function(e) {
				// Let actions do their own thing
				if($(e.target).is('.action')) return;

				var grid = this.closest('.ss-gridfield');
				if(this.data('class') == 'Folder') {
					var url = grid.data('urlFolderTemplate').replace('%s', this.data('id'));
					$('.cms-container').loadPanel(url);
					e.preventDefault();
				}
			}
		});

		$('.cms-edit-form :submit[name=action_delete]').entwine({
			onclick: function(e) {
				if(!confirm(ss.i18n._t('AssetAdmin.ConfirmDelete'))) return false;
				else this._super(e);
			}
		});

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
						var currNode = $('.cms-tree')[0].firstSelected();
						if(currNode) {
						  var url = $(currNode).find('a').attr('href');
							$('.cms-content').loadForm(url);
						}
						$('.cms-tree')[0].setCustomURL('admin/assets/getsubtree');
						$('.cms-tree')[0].reload({onSuccess: function() {
							// TODO Reset current tree node
						}});
					}
				});
				
				return false;
			}
		});
	});
}(jQuery));