/**
 * CAUTION: Assumes that a MemberTableField-instance is present as an editing form
 */
function action_addmember_right() {
	var memberTableFields = document.getElementsBySelector('#Form_EditForm div.MemberTableField');
	var tables = document.getElementsBySelector('#Form_EditForm div.MemberTableField table');
	var addLinks = document.getElementsBySelector('#Form_EditForm div.MemberTableField a.addlink');
	memberTableFields[0].openPopup(null,addLinks[0].href,tables[0]);
}

(function($) {
	$(document).ready(function() {
		var refreshAfterImport = function(e) {
			// Check for a message <div>, an indication that the form has been submitted.
			var existingFormMessage = $($(this).contents()).find('.message');
			if(existingFormMessage && existingFormMessage.html()) {
				// Refresh member listing
				var memberTableField = $(window.parent.document).find('#Form_EditForm_Members').get(0);
				if(memberTableField) memberTableField.refresh();

				// Refresh tree
				var tree = $(window.parent.document).find('#sitetree').get(0);
				if(tree) tree.reload();
			}
		};

		/**
		 * Refresh the member listing every time the import iframe is loaded,
		 * which is most likely a form submission.
		 */
		$('#MemberImportFormIframe,#GroupImportFormIframe').livequery(
			'load',
			refreshAfterImport
		);
	})
})(jQuery);