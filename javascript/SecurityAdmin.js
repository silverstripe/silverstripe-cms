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
	
	$.concrete('ss', function($){
		$('#Form_EditForm .Actions #action_addmember').concrete({
			onclick: function(e) {
				// CAUTION: Assumes that a MemberTableField-instance is present as an editing form
				var t = $('#Form_EditForm_Members');
				t[0].openPopup(
					null,
					$('base').attr('href') + t.find('a.addlink').attr('href'),
					t.find('table')[0]
				);
				return false;
			}
		});
	});
	
}(jQuery));