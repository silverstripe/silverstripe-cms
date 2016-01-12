/**
 * File: AssetAdmin.js
 */

(function($) {
	$.entwine('ss', function($){
		/**
		 * Delete selected folders through "batch actions" tab.
		 */
		/* assets don't currently have batch actions; disabling for now
		$(document).ready(function() {
			$('#Form_BatchActionsForm').entwine('.ss.tree').register(
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
		*/

		/**
		 * Load folder detail view via controller methods
		 * rather than built-in GridField view (which is only geared towards showing files).
		 */
		$('.AssetAdmin.cms-edit-form .ss-gridfield-item').entwine({
			onclick: function(e) {
				// Let actions do their own thing
				if($(e.target).closest('.action').length) {
					this._super(e);
					return;
				}

				var grid = this.closest('.ss-gridfield');
				if(this.data('class') == 'Folder') {
					var url = grid.data('urlFolderTemplate').replace('%s', this.data('id'));
					$('.cms-container').loadPanel(url);
					return false;
				}

				this._super(e);
			}
		});

		$('.AssetAdmin.cms-edit-form .ss-gridfield .col-buttons .action.gridfield-button-delete, .AssetAdmin.cms-edit-form .Actions button.action.action-delete').entwine({
			onclick: function(e) {
				var msg;
				if(this.closest('.ss-gridfield-item').data('class') == 'Folder') {
					msg = ss.i18n._t('AssetAdmin.ConfirmDelete');
				} else {
					msg = ss.i18n._t('TABLEFIELD.DELETECONFIRMMESSAGE');
				}
				if(!confirm(msg)) return false;	
				
				this.getGridField().reload({data: [{name: this.attr('name'), value: this.val()}]});
				e.preventDefault();
				return false;
			}
		});

		$('.AssetAdmin.cms-edit-form :submit[name=action_delete]').entwine({
			onclick: function(e) {
				if(!confirm(ss.i18n._t('AssetAdmin.ConfirmDelete'))) return false;	
				else this._super(e);
			}
		});

		/**
		 * Prompt for a new foldername, rather than using dedicated form.
		 * Better usability, but less flexibility in terms of inputs and validation.
		 * Mainly necessary because AssetAdmin->AddForm() returns don't play nicely
		 * with the nested AssetAdmin->EditForm() DOM structures.
		 */
		$('.AssetAdmin .cms-add-folder-link').entwine({
			onclick: function(e) {
				var name = prompt(ss.i18n._t('Folder.Name'));
				if(!name) return false;

				this.closest('.cms-container').loadPanel(this.data('url') + '&Name=' + name);
				return false;
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
							$('.cms-content').loadPanel(url);
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
