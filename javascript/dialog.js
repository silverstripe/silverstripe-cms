Behaviour.register({
	'.buttons button' : {
		onclick: function() {
			window.top._OPEN_DIALOG.execHandler( this.name );
			
			return false;
		}
		
	}
});