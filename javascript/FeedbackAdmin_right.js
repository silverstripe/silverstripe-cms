Behaviour.register({
	'#Form_EditForm' : {
		getPageFromServer : function(id) {
			statusMessage("loading...");
			
			var requestURL = 'admin/feedback/showtable/' + id;
			
			this.loadURLFromServer(requestURL);
			
			$('sitetree').setCurrentByIdx(id);
		}
	}
});