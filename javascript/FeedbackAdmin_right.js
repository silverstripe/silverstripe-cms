Behaviour.register({
	'#Form_EditForm' : {
		getPageFromServer : function(id) {
			statusMessage("loading...");
			
			var requestURL = 'admin/feedback/showtable/' + id;
			
			this.loadURLFromServer(requestURL);
			
			/*new Ajax.Request(requestURL, {
				asynchronous : true,
				method : 'post', 
				postBody : 'ajax=1',
				onSuccess : this.successfullyReceivedPage.bind(this),
				onFailure : function(response) { 
					errorMessage('error loading page',response);
				}
			});*/
		}
	}
});