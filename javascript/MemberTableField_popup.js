MemberTableFieldPopupForm = Class.extend("ComplexTableFieldPopupForm");
MemberTableFieldPopupForm.prototype = {
	initialize: function() {
		this.ComplexTableFieldPopupForm.initialize();

		Behaviour.register({
			"form#MemberTableField_Popup_DetailForm input.action": {
				onclick: this.submitForm.bind(this)
			},

			'form#MemberTableField_Popup_DetailForm input' : {
				initialise : function() {
					if(this.name == 'FirstName' || this.name == 'Surname' || this.name == 'Email') {
						var div = document.createElement('div');
						div.id = this.id + '_ac';
						div.className = 'autocomplete';
						this.parentNode.appendChild(div);
						/*
						new Ajax.Autocompleter(this.id, div.id, 'admin/security/autocomplete/' + this.name, {
							afterUpdateElement : this.afterAutocomplete.bind(this)
						});
						*/
					}
				},
				afterAutocomplete : function(field, selectedItem) {
					var data = selectedItem.getElementsByTagName('span')[1].innerHTML;
					var items = data.split(",");

					this.elements.FirstName.value = items[0];
					this.elements.Surname.value = items[1];
					this.elements.Email.value = items[2];
					this.elements.Password.value = items[3];
				}
			}

			//'form#MemberTableField_Popup_DetailForm input' : AjaxMemberLookup
		});
	}
}

MemberTableFieldPopupForm.applyTo('form#MemberTableField_Popup_DetailForm');