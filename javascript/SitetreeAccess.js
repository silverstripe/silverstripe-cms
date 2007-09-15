/**
 * UI behaviour for the "Access" tab
 *
 * Adapted from the forum module "ForumAccess.js" file.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */

ViewersGroupHide = function() {
	$('ViewersGroup').style.display = "none";
}
EditorsGroupHide = function() {
	$('EditorsGroup').style.display = "none";
}

Behaviour.register({
	'#Form_EditForm_Viewers_OnlyTheseUsers': {

		onclick: function() {
			$('ViewersGroup').style.display = "block";
		},

		initialize: function() {
			if($('Form_EditForm_Viewers_OnlyTheseUsers')) {
				if($('Form_EditForm_Viewers_OnlyTheseUsers').checked)
					$('ViewersGroup').style.display = "block";
				else
					$('ViewersGroup').style.display = "none";
			}
		}
	},

	'#Form_EditForm_Viewers_Anyone': {
		onclick: ViewersGroupHide
	},

	'#Form_EditForm_Viewers_LoggedInUsers': {
		onclick: ViewersGroupHide
	},

	'#Form_EditForm_Editors_OnlyTheseUsers': {

		onclick: function() {
			$('EditorsGroup').style.display = "block";
		},

		initialize: function() {
			if($('Form_EditForm_Editors_OnlyTheseUsers')) {
				if($('Form_EditForm_Editors_OnlyTheseUsers').checked)
					$('EditorsGroup').style.display = "block";
				else
					$('EditorsGroup').style.display = "none";
			}
		}
	},

	'#Form_EditForm_Editors_LoggedInUsers': {
		onclick: EditorsGroupHide
	}
});