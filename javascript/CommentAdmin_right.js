Behaviour.register({
	'#Form_EditForm' : {
		getPageFromServer : function(id) {
			statusMessage("loading...");
			
			var requestURL = 'admin/comments/showtable/' + id;
			
			this.loadURLFromServer(requestURL);
			
			$('sitetree').setCurrentByIdx(id);
		}
	}
});