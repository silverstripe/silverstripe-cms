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
		/**
		 * Refresh the member listing every time the import iframe is loaded,
		 * which is most likely a form submission.
		 */
		$('#MemberImportFormIframe,#GroupImportFormIframe').livequery(
			'load',
			function(e) {
				// Get iframe content
				var doc = this.document || this.contentDocument || this.contentWindow && this.contentWindow.document || null;
				if(!doc) return;
				
				// Check for a message <div>, an indication that the form has been submitted.
				var existingFormMessage = $(doc.body).find('.message');
				if(existingFormMessage && existingFormMessage.html()) {
					// Refresh member listing
					var memberTableField = $(window.parent.document).find('#Form_EditForm_Members').get(0);
					if(memberTableField) memberTableField.refresh();
				
					// Refresh tree
					var tree = $(window.parent.document).find('#sitetree').get(0);
					if(tree) tree.reload();
				}
			}
		);
})(jQuery);