/**
 * UI behaviour for the "Access" tab
 *
 * Adapted from the forum module "ForumAccess.js" file.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */

ViewersGroupHide = function() {
	$('ViewerGroups').style.display = "none";
}
EditorsGroupHide = function() {
	$('EditorGroups').style.display = "none";
}

Behaviour.register({
	'#Form_EditForm_CanViewType_OnlyTheseUsers': {

		onclick: function() {
			$('ViewerGroups').style.display = "block";
		},

		initialize: function() {
			if($('Form_EditForm_CanViewType_OnlyTheseUsers')) {
				if($('Form_EditForm_CanViewType_OnlyTheseUsers').checked)
					$('ViewerGroups').style.display = "block";
				else
					$('ViewerGroups').style.display = "none";
			}
		}
	},

	'#Form_EditForm_CanViewType_Anyone': {
		onclick: ViewersGroupHide
	},

	'#Form_EditForm_CanViewType_LoggedInUsers': {
		onclick: ViewersGroupHide
	},

	'#Form_EditForm_CanEditType_OnlyTheseUsers': {

		onclick: function() {
			$('EditorGroups').style.display = "block";
		},

		initialize: function() {
			if($('Form_EditForm_CanEditType_OnlyTheseUsers')) {
				if($('Form_EditForm_CanEditType_OnlyTheseUsers').checked)
					$('EditorGroups').style.display = "block";
				else
					$('EditorGroups').style.display = "none";
			}
		}
	},

	'#Form_EditForm_CanEditType_LoggedInUsers': {
		onclick: EditorsGroupHide
	}
});