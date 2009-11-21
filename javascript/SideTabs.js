/**
 * Generic base class for all side panels
 */
SidePanel = Class.create();
SidePanel.prototype = {
	initialize : function() {
		this.body = this.getElementsByTagName('div')[0];
	},
	destroy: function() {
		this.body = null;		
	},
	onshow : function() {
		this.onresize();
		this.body.innerHTML = '<p>loading...</p>';
		this.ajaxGetPanel(this.afterPanelLoaded);
	},
	ajaxGetPanel : function(onComplete) {
		new Ajax.Updater(this.body, this.ajaxURL(), {
			onComplete : onComplete ? onComplete.bind(this) : null,
			onFailure : this.ajaxPanelError
		});
	},
	
	ajaxPanelError : function (response) {
		errorMessage("error getting side panel", response);
	},
	
	ajaxURL : function() {
		var srcName = this.id.replace('_holder','');				
		var id = $('Form_EditForm').elements.ID;
		if(id) id = id.value; else id = "";
		
		// This assumes that admin/cms/ refers to CMSMain
		var url = 'admin/cms/' + srcName + '/' + id + '?ajax=1';
		if($('Form_EditForm_Locale')) url += "&locale=" + $('Form_EditForm_Locale').value;
		return url;
	},
	
	afterPanelLoaded : function() {
	},
	onpagechanged : function() {
	}
}

SidePanelRecord = Class.create();
SidePanelRecord.prototype = {
	onclick : function(event) {
		Event.stop(event);
		$('Form_EditForm').getPageFromServer(this.getID());
	},
	destroy: function() {
		this.onclick = null;
	},
	getID : function() {
		if(this.href.match(/\/([^\/]+)$/)) return parseInt(RegExp.$1);
	}
}





/**
 * Version list
 */
VersionList = Class.extend('SidePanel');
VersionList.prototype = {
	initialize : function() {
		this.mode = 'view';
		this.SidePanel.initialize();
	},
	destroy: function() {
		this.SidePanel = null;
		this.onclose = null;
	},
	
	ajaxURL : function() {
		return this.SidePanel.ajaxURL() + '&unpublished=' + ($('versions_showall').checked ? 1 : 0);
	},
	afterPanelLoaded : function() {
		this.idLoaded = $('Form_EditForm').elements.ID.value;
		VersionItem.applyTo('#' + this.id + ' tbody tr');
	},
	
	select : function(pageID, versionID, sourceEl) {
		if(this.mode == 'view') {
			sourceEl.select();
			var url = 'admin/getversion/' + pageID + '/' + versionID;
			if($('Form_EditForm_Locale')) url += "?locale=" + $('Form_EditForm_Locale').value;
			$('Form_EditForm').loadURLFromServer(url);
			$('viewArchivedSite').style.display = '';
			$('viewArchivedSite').getVars = '?archiveDate=' + sourceEl.getElementsByTagName('td')[1].className;
			
		} else {
			$('viewArchivedSite').style.display = 'none';

			if(this.otherVersionID) {
				sourceEl.select();
				this.otherSourceEl.select(true);
				statusMessage('Loading comparison...');
				var url = 'admin/compareversions/' + pageID + '/?From=' + this.otherVersionID + '&To=' + versionID;
				if($('Form_EditForm_Locale')) url += "&locale=" + $('Form_EditForm_Locale').value;
				$('Form_EditForm').loadURLFromServer(url);
			} else {
				sourceEl.select();
			}
		}
		this.otherVersionID = versionID;
		this.otherSourceEl = sourceEl;
	},
	onpagechanged : function() {
		if(this.idLoaded != $('Form_EditForm').elements.ID.value) {
			this.body.innerHTML = '<p>loading...</p>';
			this.ajaxGetPanel(this.afterPanelLoaded);
		}
	},
	refresh : function() {
		this.ajaxGetPanel(this.afterPanelLoaded);
	},
	onclose : function() {
		if(this.idLoaded) {
			$('Form_EditForm').getPageFromServer(this.idLoaded);
		}
		$('viewArchivedSite').style.display = 'none';
	}
}

VersionItem = Class.create();
VersionItem.prototype = {
	initialize : function() {
		this.holder = $('versions_holder');
	},
	
	destroy: function() {
		this.holder = null;
		this.onclick = null;
	},
	
	onclick : function() {
		this.holder.select(this.pageID(), this.versionID(), this);
	},
	idPair : function() {
		if(this.id.match(/page-([^-]+)-version-([^-]+)$/)) {
			return RegExp.$1 + '/' + RegExp.$2;
		}
	},
	pageID : function() {
		if(this.id.match(/page-([^-]+)-version-([^-]+)$/)) {
			return RegExp.$1;
		}
	},
	versionID : function() {
		if(this.id.match(/page-([^-]+)-version-([^-]+)$/)) {
			return RegExp.$2;
		}
	}
}

VersionAction = Class.create();
VersionAction.prototype = {
	initialize: function() {
		this.holder = $('versions_holder');

		this.showallCheckbox = $('versions_showall');
		this.showallCheckbox.holder = this;
		this.showallCheckbox.onclick = this.showall_change;

		this.comparemodeCheckbox = $('versions_comparemode');
		this.comparemodeCheckbox.holder = this;
		this.comparemodeCheckbox.onclick = function() { this.holder.comparemode_change(this.checked); }
	},
	
	showall_change: function() { 
		this.holder.holder.refresh();
	},
	
	destroy: function() {
		if(this.comparemodeCheckbox) {
			this.comparemodeCheckbox.holder = null;		
			this.comparemodeCheckbox.onclick = null;
		}
		if(this.showallCheckbox) {
			this.showallCheckbox.holder = null;		
			this.showallCheckbox.onchange = null;
		}
		this.holder = null;
		this.comparemodeCheckbox = null;		
	},
	
	/**
	 * Handler function when comparemode checkbox is clicked
	 */
	comparemode_change: function(isChecked) {
		if (true == isChecked) {
			return this.compare();
		} else {
			return this.view(); 
		}
	},
	
	view: function() {
		this.holder.mode = 'view';
	},
	
	compare: function() {
		this.holder.mode = 'compare';
	}
}


VersionItem.applyTo('#versions_holder tbody tr');
VersionAction.applyTo('#versions_holder p.pane_actions');
VersionList.applyTo('#versions_holder');

