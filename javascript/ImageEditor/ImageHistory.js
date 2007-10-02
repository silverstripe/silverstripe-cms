/**
 * @author Mateusz
 */
ImageEditor.ImageHistory = {
	
	initialize: function() {
		this.history = new Array();
		this.historyPointer = -1;
		this.modifiedOriginalImage = false;		
		this.isEnabled = true;
		this.image = ImageEditor.Positioning.addBehaviour($('image'));
		this.size = new Array();
		this.fakeImage = $('fakeImg');
		this.image = $('image');
		this.undo = ImageEditor.ImageHistory.undo.bind(this);
		this.redo = ImageEditor.ImageHistory.redo.bind(this);
		this.add = ImageEditor.ImageHistory.add.bind(this);
		this.addListeners = ImageEditor.ImageHistory.addListeners.bind(this);
		this.operationMade = ImageEditor.ImageHistory.operationMade.bind(this);		
		this.isInHistory = ImageEditor.ImageHistory.isInHistory.bind(this);
		this.onImageLoad = ImageEditor.ImageHistory.onImageLoad.bind(this);
		this.removeLastOperation = ImageEditor.ImageHistory.removeLastOperation.bind(this);
	
		this.enable = ImageEditor.ImageHistory.enable.bind(this);
		this.disable = ImageEditor.ImageHistory.disable.bind(this);
		this.clear = ImageEditor.ImageHistory.clear.bind(this);
		this.addListeners();
	},
		
	undo: function() {
		if(this.historyPointer >= 1) {
			var operation = this.history[this.historyPointer].operation;
			if(operation == 'rotate' || operation == 'crop') {
				if(this.operationMade(this.historyPointer-1,'rotate') || this.operationMade(this.historyPointer-1,'crop')) 
					this.modifiedOriginalImage = true; else this.modifiedOriginalImage = false;
			}
			Event.observe('image','load',this.onImageLoad);
			this.historyPointer = this.historyPointer - 1;
			this.image.src = this.history[this.historyPointer].fileUrl;
		} else {
			ImageEditor.statusMessageWrapper.statusMessage("No more undo","bad");
		}
	},
	
	redo: function() {
		if(this.historyPointer < this.history.length-1) {
			var operation = this.history[this.historyPointer+1].operation;
			if(operation == 'rotate' || operation == 'crop') this.modifiedOriginalImage = true;
			Event.observe('image','load',this.onImageLoad);
			this.historyPointer = this.historyPointer + 1;
			this.image.src = this.history[this.historyPointer].fileUrl;
		} else {
			ImageEditor.statusMessageWrapper.statusMessage("No more redo","bad");
		}
	},
	
	add: function(operation,url) {
		var imageWidth =  isNaN(parseInt($('image').style.width)) ? Element.getDimensions($('image')).width : parseInt($('image').style.width);//IE hack
		var imageHeight = isNaN(parseInt($('image').style.height)) ? Element.getDimensions($('image')).height : parseInt($('image').style.height);//IE hack
		//code above should be moved to Positioning.addBehaviour
		if(!this.isInHistory(operation,url)) {
			this.historyPointer++;
			this.size[this.historyPointer] = {'width': imageWidth,'height': imageHeight};
			this.history[this.historyPointer] = {'operation': operation,'fileUrl' : url};
			this.size = this.size.slice(0,this.historyPointer+1);
			this.history = this.history.slice(0,this.historyPointer+1);
			if(operation == 'rotate' || operation == 'crop') this.modifiedOriginalImage = true;
		}
	},
	
	addListeners: function() {
		this.undoListener = Event.observe('UndoButton','click',this.undo);	
		this.redoListener = Event.observe('RedoButton','click',this.redo);
	},
	
	operationMade: function(historyPointer,operation) {
		for(i=historyPointer;i>=0;i--) {
			if(this.history[i].operation == operation) {
				return true;
			}
		}
		return false;
	},
	
	enable: function() {
		if(!this.isEnabled) {
			this.addListeners();
			this.isEnabled = true;
		}
	},
	
	disable: function() {
		if(this.isEnabled) {
			Event.stopObserving($('UndoButton'),'click', this.undo);			
			Event.stopObserving($('RedoButton'),'click', this.redo);
			this.isEnabled = false;
		}
	},
	
	clear: function() {
	   this.history = new Array();
       this.historyPointer = -1;
	   this.size = new Array();
	},
	
	removeLastOperation: function() {
		this.history.pop();
		this.size.pop();
		this.historyPointer--;		
	},
	
	isInHistory: function(operation,url) {
		if(operation == 'initialize' && this.historyPointer != -1) return true;
		for(var k=0;k<this.history.length;k++) {
			if(this.history[k].operation == operation && this.history[k].fileUrl == url) {
				return true;	
			}
		}
		return false;	
	},
	
	onImageLoad: function(event) {
		Event.stopObserving($('image'),'load',this.onImageLoad);
		this.image.style.width = this.size[this.historyPointer].width + 'px';
		this.image.style.height = this.size[this.historyPointer].height + 'px';
		$('imageContainer').style.width = this.size[this.historyPointer].width + 'px';
		$('imageContainer').style.height = this.size[this.historyPointer].height + 'px';
		ImageEditor.resize.imageContainerResize.originalWidth = this.size[this.historyPointer].width;
		ImageEditor.resize.imageContainerResize.originalHeight = this.size[this.historyPointer].height;
		ImageEditor.imageToResize.onImageLoad();
	}
};