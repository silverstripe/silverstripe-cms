(function($) {
	/**
	 * Delete selected folders through "batch actions" tab.
	 */
	$(document).ready(function() {
		$('#Form_BatchActionsForm').concrete('ss').register(
			// TODO Hardcoding of base URL
			'admin/security/batchactions/delete', 
			function(ids) {
				var confirmed = confirm(
					ss.i18n.sprintf(
						ss.i18n._t('SecurityAdmin.BATCHACTIONSDELETECONFIRM'),
						ids.length
					)
				);
				return (confirmed) ? ids : false;
			}
		);
	});
}(jQuery));
/**
 * CAUTION: Assumes that a MemberTableField-instance is present as an editing form
 */
function action_addmember_right() {
	var memberTableFields = document.getElementsBySelector('#Form_EditForm div.MemberTableField');
	var tables = document.getElementsBySelector('#Form_EditForm div.MemberTableField table');
	var addLinks = document.getElementsBySelector('#Form_EditForm div.MemberTableField a.addlink');
	memberTableFields[0].openPopup(null,addLinks[0].href,tables[0]);
}