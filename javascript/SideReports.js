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
		var url = 'admin/sidereport/' + this.selector.value;
		if ($('SideReportForm')) {
			url += '?'+Form.serialize('SideReportForm');
			if($('LangSelector')) url += "&locale=" + $('LangSelector').value;
		} else {
			if($('LangSelector')) url += "?locale=" + $('LangSelector').value;
		}
		return url;
	},
	reportSelected: function() {
		var value = this.selector.value;
		if ($('SideReportForm')) {
			$('SideReportForm').parentNode.removeChild($('SideReportForm'));
		}
		if ($('SideReportForm_'+this.selector.value)) {
			// Copy form content...
			var form = '<form id="SideReportForm">'+
				$('SideReportForm_'+this.selector.value).innerHTML
				+'</form>';
			$('ReportSelector_holder').innerHTML += form;
		}
		
		this.selector = $('ReportSelector');
		this.selector.value = value;
		if(this.selector) this.selector.holder = this;
		Behaviour.register({
			'#ReportSelector' : {
				onchange : function() {
					$('reports_holder').reportSelected();
				}
			}
		});
	}
}

SideReportGo = Class.create();
SideReportGo.prototype = {
	destroy: function() {
		this.onclick = null;
		this.holder = null;
	},
	onclick: function() {
		$('reports_holder').showreport();
	}
}


SideReportRecord = Class.create();
SideReportRecord.prototype = {
	destroy: function() {
		this.onclick = null;
	},
	
	onclick : function(event) {
		if (!this.getAttribute('reloadCMS')) {
			Event.stop(event);
			$('sitetree').loadingNode = $('sitetree').getTreeNodeByIdx( this.getID() );
			$('Form_EditForm').getPageFromServer(this.getID());
		}
	},
	getID : function() {
		if(this.href.match(/\/([^\/]+)$/)) return parseInt(RegExp.$1);
	}
}

SideReportGo.applyTo('#report_select_go');
SideReportRecord.applyTo('#reports_holder a');
SideReports.applyTo('#reports_holder');

Behaviour.register({
	'#ReportSelector' : {
		onchange : function() {
			$('reports_holder').reportSelected();
		}
	}
});
