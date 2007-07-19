SideReports = Class.extend('SidePanel');
SideReports.prototype = {
	initialize: function() {
		this.selector = $('ReportSelector');
		if(this.selector) this.selector.holder = this;
		this.SidePanel.initialize();
	},
	
	destroy: function() {
		if(this.SidePanel) this.SidePanel.destroy();
		this.SidePanel = null;
		if(this.selector) this.selector.holder = null;
		this.selector = null;
	},
	
	onshow: function() {
		if(this.selector.value) this.showreport();
	},

	/**
	 * Retrieve a report via ajax
	 */	
	showreport: function() {
		if(this.selector.value) {
			this.body.innerHTML = '<p>loading...</p>';
			this.ajaxGetPanel(this.afterPanelLoaded);
		} else {
			this.body.innerHTML = "<p>choose a report in the dropdown.</p>";
		}
	},
	afterPanelLoaded : function() {
		SideReportRecord.applyTo('#' + this.id + ' a');
	},
	ajaxURL: function() {
		return 'admin/sidereport/' + this.selector.value;
	}
	
}

SideReportDropdown = Class.create();
SideReportDropdown.prototype = {
	destroy: function() {
		this.onchange = null;
		this.holder = null;
	},
	onchange: function() {
		this.holder.showreport();
	}
}


SideReportRecord = Class.create();
SideReportRecord.prototype = {
	destroy: function() {
		this.onclick = null;
	},
	
	onclick : function(event) {
		Event.stop(event);
		$('sitetree').loadingNode = $('sitetree').getTreeNodeByIdx( this.getID() );
		$('Form_EditForm').getPageFromServer(this.getID());
	},
	getID : function() {
		if(this.href.match(/\/([^\/]+)$/)) return parseInt(RegExp.$1);
	}
}

SideReportDropdown.applyTo('#ReportSelector');
SideReportRecord.applyTo('#reports_holder a');
SideReports.applyTo('#reports_holder');