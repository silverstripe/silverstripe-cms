/**
 * UI behaviour for the "Access" tab
 */

var siteTreeAccessHandler = function(canField, groupsField) {
	var output = {}
	output['#Form_EditForm_' + canField + ' input'] = {
		initialize: function() {
			if(this.checked) this.click();
		},
		onclick: function() {
			$(groupsField).style.display = (this.value == 'OnlyTheseUsers') ? 'block' : 'none';
			$(groupsField).style.visibility = (this.value == 'OnlyTheseUsers') ? 'visible' : 'hidden';
		}
	}
	return output;
};

Behaviour.register(siteTreeAccessHandler('CanViewType', 'ViewerGroups'));
Behaviour.register(siteTreeAccessHandler('CanEditType', 'EditorGroups'));