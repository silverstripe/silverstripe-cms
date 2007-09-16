Behaviour.register({
	'div.inlineformaction input#Form_EditForm_createtranslation': {
		onclick: function() {

			var st = $('sitetree');
			var originalID = st.getIdxOf(st.firstSelected());
			if(originalID && originalID.substr(0,3) == 'new') {
				alert("You have to save a page before translating it");
			} else {
				var url = baseHref() + 'admin/' + this.name.substring(7) + '?ID=' + $('Form_EditForm_ID').value + '&newlang=' +
				$('Form_EditForm_NewTransLang').value + '&ajax=1';
				
				new Ajax.Request( url, {
					onSuccess: Ajax.Evaluator,
					onFailure: Ajax.Evaluator
				});
				
				return false;
			}
		}
	}
});