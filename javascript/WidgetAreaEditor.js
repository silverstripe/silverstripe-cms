WidgetAreaEditorClass = Class.create();
WidgetAreaEditorClass.applyTo('div.WidgetAreaEditor');

WidgetAreaEditorClass.prototype = {
	initialize: function() {
		UsedWidget.applyToChildren($('WidgetAreaEditor_usedWidgets'), 'div.Widget');
	
		// Make available widgets draggable
		var availableWidgets = $('WidgetAreaEditor_availableWidgets').childNodes;
		for(var i = 0; i < availableWidgets.length; i++) {
			var widget = availableWidgets[i];
			if(widget.id)
				new Draggable(widget.id);
		}
		
		// Create dummy sortable to prevent javascript errors
		Sortable.create('WidgetAreaEditor_availableWidgets', {tag: 'li', handle: 'handle', containment: []});
		// Used widgets are sortable
		Sortable.create('WidgetAreaEditor_usedWidgets', {tag: 'div', handle: 'handle', containment: ['WidgetAreaEditor_availableWidgets', 'WidgetAreaEditor_usedWidgets'], onUpdate: this.updateWidgets});
		
		// Figure out maxid, this is used when creating new widgets
		this.maxid = 0;
		
		var usedWidgets = $('WidgetAreaEditor_usedWidgets').childNodes;
		for(var i = 0; i < usedWidgets.length; i++) {
			var widget = usedWidgets[i];
			if(widget.id) {
				widgetid = widget.id.match(/Widget\[([0-9]+)\]/i);
				if(widgetid && parseInt(widgetid[1]) > this.maxid)
					this.maxid = parseInt(widgetid[1]);
			}
		}
		
		// Ensure correct sort values are written when page is saved
		$('Form_EditForm').observeMethod('BeforeSave', this.beforeSave.bind(this));
	},
	
	beforeSave: function() {
		// Ensure correct sort values are written when page is saved
		var usedWidgets = $('WidgetAreaEditor_usedWidgets');
		
		if(usedWidgets) {
			this.sortWidgets();
		
			var children = usedWidgets.childNodes;
		
			for( var i = 0; i < children.length; ++i ) {
				var child = children[i];
			
				if(child.beforeSave) {
					child.beforeSave();
				}
			}
		}
	},
	
	updateWidgets: function() {
		// This is called when an available widgets is dragged over to used widgets.
		// It inserts the editor form into the new used widget
		var usedWidgets = $('WidgetAreaEditor_usedWidgets').childNodes;
		for(var i = 0; i < usedWidgets.length; i++) {
			var widget = usedWidgets[i];
			if(widget.id && (widget.id.indexOf("Widget[") != 0) && (widget.id != 'NoWidgets')) {
				new Ajax.Request('Widget_Controller/EditableSegment/' + widget.id, {onSuccess : $('WidgetAreaEditor_usedWidgets').parentNode.parentNode.insertWidgetEditor.bind(this)});
			}
		}
	},
	
	insertWidgetEditor: function(response) {
		// Remove placeholder text
		if($('NoWidgets')) {
			$('WidgetAreaEditor_usedWidgets').removeChild($('NoWidgets'));
		}
	
		// Find the new widget
		var usedWidgets = $('WidgetAreaEditor_usedWidgets').childNodes;
		for(var i = 0; i < usedWidgets.length; i++) {
			var widget = usedWidgets[i];
			if(widget.id && (widget.id.indexOf("Widget[") != 0)) {
				// Clone the widget so we can put it back in the available widgets column
				clone = widget.cloneNode(true);
				
				// Give the widget a unique id
				widget.innerHTML = response.responseText.replace(/Widget\[0\]/gi, "Widget[new-" + (++$('WidgetAreaEditor_usedWidgets').parentNode.parentNode.maxid) + "]");
				
				// Replace the available widget with the used widget with editor form
				widget.parentNode.insertBefore($(widget).getElementsByClassName('Widget')[0], widget);
				widget.parentNode.removeChild(widget);
				
				// Put the clone into the available widgets column
				$('WidgetAreaEditor_availableWidgets').appendChild(clone);
				
				// Reapply behaviour
				new Draggable(clone.id);
				Sortable.create('WidgetAreaEditor_usedWidgets', {tag: 'div', handle: 'handle', containment: ['WidgetAreaEditor_availableWidgets', 'WidgetAreaEditor_usedWidgets'], onUpdate: $('WidgetAreaEditor_usedWidgets').parentNode.parentNode.updateWidgets});
				UsedWidget.applyToChildren($('WidgetAreaEditor_usedWidgets'), 'div.Widget');
				return;
			}
		}
	},
	
	sortWidgets: function() {
		// Order the sort by the order the widgets are in the list
		var usedWidgets = $('WidgetAreaEditor_usedWidgets');
		
		if(usedWidgets) {
			widgets = usedWidgets.childNodes;
			
			for(i = 0; i < widgets.length; i++) {
				var div = widgets[i];

				if(div.nodeName != '#comment') {
					var fields = div.getElementsByTagName('input');
					
					for(j = 0; field = fields.item(j); j++) {
						if(field.name == div.id + '[Sort]') {
							field.value = i;
						}
					}
				}
				
			}
		}
	},
	
	deleteWidget: function(widgetToRemove) {
		// Remove a widget from the used widgets column
		$('WidgetAreaEditor_usedWidgets').removeChild(widgetToRemove);
	}
}

UsedWidget = Class.create();

UsedWidget.prototype = {
	initialize: function() {
		// Call deleteWidget when delete button is pushed
		this.deleteButton = this.findDescendant('span', 'widgetDelete');
		if(this.deleteButton)
			this.deleteButton.onclick = this.deleteWidget.bind(this);
	},
	
	// Taken from FieldEditor
	findDescendant: function(tag, clsName, element) {
		if(!element)
			element = this;
		
		var descendants = element.getElementsByTagName(tag);
		
		for(var i = 0; i < descendants.length; i++) {
			var el = descendants[i];
			
			if(tag.toUpperCase() == el.tagName && el.className.indexOf( clsName ) != -1)
				return el;
		}
		
		return null;
	},
	
	deleteWidget: function() {
		this.parentNode.parentNode.parentNode.deleteWidget(this);
	}
}

