/**
 * @author Mateusz
 */
ImageEditor = {};
ImageEditor.Point = {
	initialize: function(x,y) {
		this.x = x;
		this.y = y;
	}
}

ImageEditor.EventStack = {
	lastEventElement: null,
	getLastEventElement: function(){		
		return ImageEditor.EventStack.lastEventElement;
	},
	
	addEvent: function(event) {
		ImageEditor.EventStack.lastEventElement = Event.element(event);
	},
	
	clearStack: function() {
		this.lastEventElement  = null;
	}
}

ImageEditor.Positioning = {
	addBehaviour: function(element) {
		this.element = element;		
		this.element.getTop = ImageEditor.Positioning.getTop.bind(this);
		this.element.getLeft = ImageEditor.Positioning.getLeft.bind(this);
		this.element.getWidth = ImageEditor.Positioning.getWidth.bind(this);
		this.element.getHeight = ImageEditor.Positioning.getHeight.bind(this);
		this.element.getParentLeft = ImageEditor.Positioning.getParentLeft.bind(this);
		this.element.getParentTop = ImageEditor.Positioning.getParentTop.bind(this);
		this.element.getParentHeight = ImageEditor.Positioning.getParentHeight.bind(this);
		this.element.getParentWidth = ImageEditor.Positioning.getParentWidth.bind(this);
		return this.element;
	},
	
	getTop: function() {
		return Position.positionedOffset(this.element)[1];
	},
	
	getLeft: function() {
		return parseInt(this.element.style.left);
	},
	
	getWidth: function() {
		return Element.getDimensions(this.element).width;
	},
	
	getHeight: function() {
		return Element.getDimensions(this.element).height;
	}, 
	
	getParentLeft: function() {
		var parentLeft = Position.cumulativeOffset(Position.offsetParent(this.element))[0];
		return parentLeft;
	},
	
	getParentTop: function() {
		var parentTop = Position.cumulativeOffset(Position.offsetParent(this.element))[1];
		return parentTop;
	},
	
	getParentHeight: function() {
		return Element.getDimensions(Position.offsetParent(this.element)).height;
	},
	
	getParentWidth: function() {
		return Element.getDimensions(Position.offsetParent(this.element)).width
	}
}

ImageEditor.Random = {
	string: function(length) {
		var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		var string = "";
		var i = 0;
		for(x=0;x<length;x++) {
			i = Math.floor(Math.random() * 57);
			string += chars.charAt(i);
		}	
		return string;
	}
}

ImageEditor.StatusMessage = {
    initialize: function() {
        this.statusMessage = ImageEditor.StatusMessage.statusMessage.bind(this);
        window.frameElement.statusMessage = window.top.statusMessage;
        var s1 = $('statusMessage');
        var s2 = window.top.document.getElementById('statusMessage');
        s1.showMessage = s2.showMessage;
        s1.clearMessage = s2.clearMessage;
        s1.fade = s2.fade;
        s1.afterFade = s2.afterFade;
        this.statusMessageContainer = s1;
    },
    
    statusMessage: function(msg, type, clearManually) {
        window.frameElement.statusMessage(msg, type, clearManually,this.statusMessageContainer);
    } 
}   
Event.observe(window,'load',function(e) {ImageEditor.statusMessageWrapper = new ImageEditor.StatusMessage.initialize();});