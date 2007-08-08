CommentTableField = Class.create();
CommentTableField.prototype = {
	initialize: function() {
		var rules = {};
		
		rules['#'+this.id+' table.data a.spamlink'] = {
			onclick: this.removeRowAfterAjax.bind(this)
		};
		
		rules['#'+this.id+' table.data a.acceptlink'] = {
			onclick: this.removeRowAfterAjax.bind(this)
		};
		
		rules['#'+this.id+' table.data a.hamlink'] = {
			onclick: this.removeRowAfterAjax.bind(this)
		};
		
		Behaviour.register(rules);
	},
	
	removeRowAfterAjax: function(e) {
		var img = Event.element(e);
		var link = Event.findElement(e,"a");
		var row = Event.findElement(e,"tr");
		
		img.setAttribute("src",'cms/images/network-save.gif'); // TODO doesn't work in Firefox1.5+
		new Ajax.Request(
			link.getAttribute("href"),
			{
				method: 'post', 
				postBody: 'forceajax=1',
				onComplete: function(){
					Effect.Fade(row);
				}.bind(this),
				onFailure: ajaxErrorHandler
			}
		);
		Event.stop(e);
	}
}

CommentTableField.applyTo('div.CommentTableField');