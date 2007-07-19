Behaviour.register({
	'body' : {
		onload: function() {
			top.getElementById('ImportFile').frameLoaded( document );
		}
	},
	
	'form input[type=file]' : {
		onchange: function() {
			this.form.submit();
		}
	}
});