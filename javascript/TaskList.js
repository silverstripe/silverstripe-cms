TaskList = Class.extend('SidePanel');
TaskList.prototype = {
	destroy: function() {
		if(this.SidePanel) this.SidePanel.destroy();
		this.SidePanel = null;
	},
	/**
	 * Called after the panel has been ajax-loaded in
	 */
	afterPanelLoaded : function() {
		TaskListRecord.applyTo('#' + this.id + ' a');
	}
}

TaskListRecord = SidePanelRecord;

TaskListRecord.applyTo('#tasklist_holder a');
TaskList.applyTo('#tasklist_holder');

TaskListRecord.applyTo('#waiting_holder a');
TaskList.applyTo('#waitingon_holder');
