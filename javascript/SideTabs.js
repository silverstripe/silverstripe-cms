// script.aculo.us EffectResize.js
// Copyright(c) 2007 - Frost Innovation AS, http://ajaxwidgets.com
//
// EffectResize.js is freely distributable under the terms of an MIT-style license.
// For details, see the script.aculo.us web site: http://script.aculo.us/
//
// From: http://wiki.script.aculo.us/scriptaculous/show/Effect+Resize+Extension
// Modified to work with Prototype 1.4.0_rc3

/* Helper Effect for resizing elements...
 */
Effect.ReSize = Class.create();
Object.extend(Object.extend(Effect.ReSize.prototype, Effect.Base.prototype), {
	initialize: function(element) {
		this.element = element;
		if(!this.element) throw(Effect._elementDoesNotExistError);
		var options = Object.extend({ amount: 100, direction: 'vert', toSize:null }, arguments[1] || {});
		if( options.direction == 'vert' )
			this.originalSize = options.originalSize || parseInt(this.element.style.height);
		else
			this.originalSize = options.originalSize || parseInt(this.element.style.width);

		if( options.toSize != null ) {
			options.amount = options.toSize - this.originalSize;
		}

		this.start(options);
	},

	setup: function() {
		// Prevent executing on elements not in the layout flow
		if(this.element.style.display == 'none') { this.cancel(); return; }
  	},
	
	update: function(position) {
		if( this.options.direction == 'vert' ) {
			this.element.style.height = this.originalSize+(this.options.amount*position)+'px';
		} else {
			this.element.style.width = this.originalSize+(this.options.amount*position)+'px';
		}
	},
  
	finish: function() {
		if( this.options.direction ==  'vert' ) {
			this.element.style.height = this.originalSize+this.options.amount+'px';
			// Modification to make 'Page Version History' resize correctly:
			if(this.element.onresize) this.element.onresize();
		} else {
			this.element.style.width = this.originalSize+this.options.amount+'px';
		}
	}
});
// End: script.aculo.us EffectResize.js

SideTabs = Class.create();
SideTabs.prototype = {
	/**
	 * Set up the SideTab set.  Note that tabs themselves are automatically constructed
	 * from the HTML.
	 */
	initialize: function() {
		this.tabs = this.getElementsByTagName('h2');
		this.resize();
		$('Form_EditForm').observeMethod('PageLoaded',this.onpagechanged.bind(this));
	},
	
	destroy: function() {
		this.tabs = null;
	},
	
	/**
	 * Fix all the panes' sizes in response to one being opened or closed
	 *
	 * @param useEffect boolean Use smooth resizing effect
	 */
	resize: function(useEffect) {
		var i,numOpenPanes=0,otherPanes = [];
		for(i=0;i<this.tabs.length;i++) {
			if(this.tabs[i].selected) {
				numOpenPanes++;
			}
			otherPanes[otherPanes.length] = this.tabs[i].linkedPane;
		}
		if(numOpenPanes > 0) {
			// We no longer hide the left frame
			// $('left').show();
			var totalHeight = getFittingHeight(this.tabs[0].linkedPane, 0, otherPanes);
			var eachHeight = totalHeight / numOpenPanes;
			for(i=0;i<this.tabs.length;i++) {
				if(this.tabs[i].selected) {
					if (useEffect == true) {
						new Effect.ReSize(this.tabs[i].linkedPane, {direction:'vert', toSize:eachHeight, duration:.4});
					} else {
						this.tabs[i].linkedPane.style.height = eachHeight + 'px';
					}
					if(this.tabs[i].linkedPane.onresize) this.tabs[i].linkedPane.onresize();
				}
			}
			
		// All panes closed, hide the whole left-hand panel
		} else {
			// Don't collapse it when all are closed. Can be resized manually
			// $('left').hide();
		}
	},
	
	/**
	 * Refresh all the panes after we change to a new page
	 */
	onpagechanged: function() {
		var item,i;
		for(i=0;item=this.tabs[i];i++) {
			if(this.tabs[i].selected && this.tabs[i].linkedPane.onpagechanged) this.tabs[i].linkedPane.onpagechanged();
		}
	}
}

SideTabItem = Class.create();

SideTabItem.prototype = {
	/**
	 * Set up one of the side tabs
	 */
	initialize: function() {
		var holderID = this.id.replace('heading_','') + '_holder';
		this.linkedPane = $(holderID);
		if(!this.linkedPane) throw("Can't find item: " + holderID);
		this.selected = (this.className && this.className.indexOf('selected') > -10);
		this.holder = $('treepanes');
		this.linkedPane.style.display = this.selected ? '' : 'none';
	},
	
	destroy: function() {
		this.holder = null;
		this.linkedPane = null;
		this.onclick = null;
	},
	
	/**
	 * Handler for <h2> click
	 */
	onclick: function(event) {
		Event.stop(event);
		var toggleID = this.id.replace('heading_','') + '_toggle';
		Element.toggle(toggleID + '_closed');
		Element.toggle(toggleID + '_open');
		this.toggle();
	},
	toggle: function() {
		if(this.selected) this.close();
		else this.open();
	},
	open: function() {
		if(!this.selected) {
			this.selected = true;
			Element.addClassName(this,'selected');
			this.linkedPane.style.display = '';
			this.linkedPane.style.height = '0px';
			this.holder.resize(true);
			if(this.linkedPane.onshow) {
				this.linkedPane.onshow();
			}
		}
	},
	close: function() {
		if(this.selected) {
			this.selected = false;
			Element.removeClassName(this,'selected');
			new Effect.ReSize(this.linkedPane, {direction:'vert', toSize:0, duration:.4});
			this.holder.resize(true);
			if(this.holder.onhide) this.holder.onhide();
			if(this.linkedPane.onclose) this.linkedPane.onclose();
		}		
	}
}

// Application order is important - the Items must be created before the SideTabs object.
SideTabItem.applyTo('#treepanes h2');
SideTabs.applyTo('#treepanes');

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
	onresize : function() {
		fitToParent(this.body);
	},
	ajaxGetPanel : function(onComplete) {
		fitToParent(this.body);
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
 * Class that will add an 'over' class when the mouse is over the object
 */
Highlighter = Class.create();
Highlighter.prototype = {
	onmouseover: function() {
		Element.addClassName(this,'over');
	},
	onmouseout: function() {
		Element.removeClassName(this,'over');
	},
	destroy: function() {
		this.onmouseover = null;
		this.onmouseout = null;		
	},
	select: function(dontHide) {
		if(dontHide) {
			Element.addClassName(this,'current');
			this.parentNode.lastSelected = null;
			
		} else {
			if(this.parentNode.lastSelected) {
				Element.removeClassName(this.parentNode.lastSelected,'current');
				Element.addClassName(this,'current');
				
			} else {
				var i,item;
				for(i=0;item=this.parentNode.childNodes[i];i++) {
					if(item.tagName) {
						if(item == this) Element.addClassName(item,'current');
						else Element.removeClassName(item,'current');
					}
				}
			}
			this.parentNode.lastSelected = this;
		}
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
		} else {
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
	}
}

VersionItem = Class.extend('Highlighter');
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

