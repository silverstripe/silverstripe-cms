MarkingPropertiesButton = Class.create();
MarkingPropertiesButton.applyTo('#Form_EditForm_deletemarked', "Please select some files to delete!", 'deletemarked', 'Do you really want to delete the marked files?');

MarkingPropertiesButton.prototype = {
	initialize: function(noneCheckedError, action, confirmMessage) {
		this.noneCheckedError = noneCheckedError;
		this.action = action;
		this.confirmMessage = confirmMessage;
	},
	
	onclick: function() {
		var i, list = "", checkboxes = $('Form_EditForm').elements['Files[]'];
		if(!checkboxes) checkboxes = [];
		if(!checkboxes.length) checkboxes = [ checkboxes ];
		for(i=0;i<checkboxes.length;i++) {
			if(checkboxes[i].checked) list += (list?',':'') + checkboxes[i].value;
		}
		
		if(list == "") {
			alert(this.noneCheckedError);
			return false;
			
		} else {
			$('Form_EditForm_FileIDs').value = list;
		}
		// If there is a confirmation message, show it before submitting
		if('' != this.confirmMessage) {
			// Only submit if OK button is clicked
			if (confirm(this.confirmMessage)) {
				$('Form_EditForm').save(false, null, this.action);
			}
		} else {
			$('Form_EditForm').save(false, null, this.action);
		}
		return false;
	}
}


// CheckBoxRange adapted from: http://jroller.com/page/rmcmahon?entry=checkboxrange_with_prototype
var CheckBoxRange = Class.create();

CheckBoxRange.prototype = {
	currentBox: null,
	form: null,
	field: null,

	initialize: function(form, field) {
		this.form = form;
		this.field = field;
		this.eventPossibleCheckHappened = this.possibleCheckHappened.bindAsEventListener(this);
		if(form) {
			Event.observe(form, "click", this.eventPossibleCheckHappened);
			Event.observe(form, "keyup", this.eventPossibleCheckHappened);
		}
	},
		
	possibleCheckHappened: function(event) {
		var target = Event.element(event);
			
		if ((event.button == 0 || event.keyCode == 32 || event.keyCode == 17) && 
			this.isCheckBox(target) && target.form == $(this.form) && target.name == this.field) {
			// If ctrl or shift is keys are pressed
			if ((event.shiftKey || event.ctrlKey  ) && this.currentBox)
				this.updateCheckBoxRange(this.currentBox, target);
		this.currentBox = target;
		}
	},

	isCheckBox: function(e) {
		return (e.tagName.toLowerCase() == "input" && e.type.toLowerCase() == "checkbox");
	},

	updateCheckBoxRange: function(start, end) {
		var last_clicked = end;
		var checkboxes = Form.getInputs(this.form, 'checkbox', this.field);
		var checkbox;
		var last;
		
		for (var i=0; (checkbox = checkboxes[i]); ++i) {
		if (checkbox == end) {
			last = start;
			break;
		}
		if (checkbox == start) {
			last = end;
			break;
		}
		}
		
		for (; (checkbox = checkboxes[i]); ++i) {
			if (checkbox != last_clicked && checkbox.checked != last_clicked.checked)
				checkbox.click();
			if (checkbox == last)
				break;
		}
	}
}

// SubsDraggable adapted from http://dev.rubyonrails.org/ticket/5771

// extentions for scriptaculous dragdrop.js
Object.extend(Class, {
	superrise: function(obj, names){
		names.each( function(n){ obj['super_' + n] = obj[n] } )
		return obj;
	}
})

// Draggable that allows substitution of draggable element
var SubsDraggable = Class.create();

SubsDraggable.prototype = Object.extend({}, Draggable.prototype);
Class.superrise(SubsDraggable.prototype, ['initialize', 'startDrag', 'finishDrag'])
Object.extend( SubsDraggable.prototype , {
	initialize: function(event) {
		this.super_initialize.apply(this, arguments);
		if( typeof(this.options.dragelement) == 'undefined' ) this.options.dragelement = false;
	},
	startDrag: function(event) {
		if( this.options.dragelement ) {
			this._originalElement = this.element;
			// Get the id of the file being dragged
			var beingDraggedId = this.element.id.replace('drag-Files-','');
			this.element = this.options.dragelement(this.element);
			Position.absolutize(this.element);
			Position.clone(this._originalElement, this.element);
			// Add # files being moved message
			this.element.className = 'dragfile DraggedHandle';
			// We are at least moving the 1 file being dragged
			var numMoved = 1;
			var i, checkboxes = $('Form_EditForm').elements['Files[]'];
			if(!checkboxes) checkboxes = [];
			if(!checkboxes.length) checkboxes = [ checkboxes ];
			for(i=0;i<checkboxes.length;i++) {
				// Total up the other files that are checked
				if(checkboxes[i].checked && checkboxes[i].value != beingDraggedId) {
					numMoved++;
				}
			}
			numFilesIndicator = document.createElement('span');
			numFilesIndicator.innerHTML = 'Moving ' + numMoved + ' files';
			numFilesIndicator.className = 'NumFilesIndicator';
			this.element.appendChild(numFilesIndicator);
		}
		this.super_startDrag(event);
	},
	finishDrag: function(event, success) {
		this.super_finishDrag(event, success);
	
		if(this.options.dragelement){
			Element.remove(this.element);
			this.element = this._originalElement;
			this._originalElement = null;
		}
	}
})
// gets element that should be dragged instead of original element
// returned element should be added to DOM tree, and will be deleted by dragdrop library
function getDragElement(element){
	var el = element.cloneNode(true);
	el.id = '';
	document.body.appendChild(el);
	return el;
}

// Set up DRAG handle
DragFileItem = Class.create();
DragFileItem.prototype = {
	initialize: function() {
			if (this.id)
			{
				this.draggable = new SubsDraggable(this.id, {revert:true,ghosting:false,dragelement:getDragElement});
			}
	},
	destroy: function() {
		this.draggable = null;
	}
}
DragFileItem.applyTo('#Form_EditForm_Files tr td.dragfile');

// Set up folder drop target
DropFileItem = Class.create();
DropFileItem.prototype = {
	initialize: function() {
		// Get this.recordID from the last "-" separated chunk of the id HTML attribute
		// eg: <li id="treenode-6"> would give a recordID of 6
		if(this.id && this.id.match(/-([^-]+)$/))
			this.recordID = RegExp.$1;
		this.droppable = Droppables.add(this.id, {accept:'dragfile', hoverclass:'filefolderhover',
			onDrop:function(droppedElement) {
				// Get this.recordID from the last "-" separated chunk of the id HTML attribute
				// eg: <li id="treenode-6"> would give a recordID of 6
				if(this.element.id && this.element.id.match(/-([^-]+)$/))
					this.recordID = RegExp.$1;
				$('Form_EditForm').elements['DestFolderID'].value = this.recordID;

				// Add the dropped file to the list of files to move
				var list = droppedElement.getElementsByTagName('img')[0].id.replace('drag-img-Files-','');
				var i, checkboxes = $('Form_EditForm').elements['Files[]'];
				if(!checkboxes) checkboxes = [];
				if(!checkboxes.length) checkboxes = [ checkboxes ];
				// Add each checked file to the list of ones to move
				for(i=0;i<checkboxes.length;i++) {
					if(checkboxes[i].checked) list += (list?',':'') + checkboxes[i].value;
				}
				$('Form_EditForm_FileIDs').value = list;
				$('Form_EditForm').save(false, null, 'movemarked')
			}
		});
	},
	destroy: function() {
		this.droppable = null;
		this.recordID = null;
	}
}
DropFileItem.applyTo('#sitetree li');