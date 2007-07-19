CommentList = Class.extend('SidePanel');
CommentList.prototype = {
	destroy: function() {
		if(this.SidePanel) this.SidePanel.destroy();
		this.SidePanel = null;
	},
	onpagechanged : function() {
		this.body.innerHTML = '<p>loading...</p>';
		this.ajaxGetPanel(this.afterPanelLoaded);
	}
}

CommentList.applyTo('#comments_holder');