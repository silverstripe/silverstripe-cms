MemberTableFieldPopupForm = Class.extend("ComplexTableFieldPopupForm");
MemberTableFieldPopupForm.prototype = {
	initialize: function() {
		this.ComplexTableFieldPopupForm.initialize();

		Behaviour.register({
			"form#MemberTableField_Popup_DetailForm .Actions input.action": {
				onclick: this.submitForm.bind(this)
			}
		});
	}
}

MemberTableFieldPopupForm.applyTo('form#MemberTableField_Popup_DetailForm');