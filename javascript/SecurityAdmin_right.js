/**
 * CAUTION: Assumes that a MemberTableField-instance is present as an editing form
 */
function action_addmember_right() {
	var memberTableFields = document.getElementsBySelector('#Form_EditForm div.MemberTableField');
	var tables = document.getElementsBySelector('#Form_EditForm div.MemberTableField table');
	var addLinks = document.getElementsBySelector('#Form_EditForm div.MemberTableField a.addlink');
	memberTableFields[0].openPopup(null,addLinks[0].href,tables[0]);
}