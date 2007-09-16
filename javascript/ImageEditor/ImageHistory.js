/**
 * @author Mateusz
 */
ImageHistory = {
	
	initialize: function() {
		this.history = new Array();
		this.historyPointer = -1;
		this.modifiedOriginalImage = false;		
		this.isEnabled = true;
		this.image = Positioning.addBehaviour($('image'));
		this.size = new Array();
		this.fakeImage = $('fakeImg');
		this.image = $('image');
		this.undo = ImageHistory.undo.bind(this);
		this.redo = ImageHistory.redo.bind(this);
		this.add = ImageHistory.add.bind(this);
		this.addListeners = ImageHistory.addListeners.bind(this);
		this.operationMade = ImageHistory.operationMade.bind(this);		
		this.isInHistory = ImageHistory.isInHistory.bind(this);
		this.onImageLoad = ImageHistory.onImageLoad.bind(this);
		this.removeLastOperation = ImageHistory.removeLastOperation.bind(this);
	
		this.enable = ImageHistory.enable.bind(this);
		this.disable = ImageHistory.disable.bind(this);
		this.clear = ImageHistory.clear.bind(this);
		this.addListeners();	
	},
		
	undo: function() {
		if(this.historyPointer >= 1) {
			operation = this.history[this.historyPointer].operation;
			if(operation == 'rotate' || operation == 'crop') {
				if(this.operationMade(this.historyPointer-1,'rotate') || this.operationMade(this.historyPointer-1,'crop')) 
					this.modifiedOriginalImage = true; else this.modifiedOriginalImage = false;
			}
			Event.observe('image','load',this.onImageLoad);
			this.historyPointer = this.historyPointer - 1;
			this.image.src = this.history[this.historyPointer].fileUrl;
		} else {
			alert("No more undo");
		}
	},
	
	redo: function() {
		if(this.historyPointer < this.history.length-1) {
			operation = this.history[this.historyPointer+1].operation;
			if(operation == 'rotate' || operation == 'crop') this.modifiedOriginalImage = true;
			Event.observe('image','load',this.onImageLoad);
			this.historyPointer = this.historyPointer + 1;
			this.image.src = this.history[this.historyPointer].fileUrl;
		} else {
			alert("No more redo");
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
		this.undoListener = Event.observe('undoButton','click',this.undo);	
		this.redoListener = Event.observe('redoButton','click',this.redo);
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
			Event.stopObserving($('undoButton'),'click', this.undo);			
			Event.stopObserving($('redoButton'),'click', this.redo);
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
		imageBox.checkOutOfDrawingArea(this.size[this.historyPointer].width,this.size[this.historyPointer].height);
		this.image.style.width = this.size[this.historyPointer].width + 'px';
		this.image.style.height = this.size[this.historyPointer].height + 'px';
		$('imageContainer').style.width = this.size[this.historyPointer].width + 'px';
		$('imageContainer').style.height = this.size[this.historyPointer].height + 'px';
		resize.imageContainerResize.originalWidth = this.size[this.historyPointer].width;
		resize.imageContainerResize.originalHeight = this.size[this.historyPointer].height;
		imageToResize.onImageLoad();
	}
};