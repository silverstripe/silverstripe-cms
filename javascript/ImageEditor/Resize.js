/**
 * @author Mateusz
 */
ImageEditor.Resize = {
	
	initialize: function(element) {		
		this.element = element;
		this.leftBoxConstraint = 1;
		this.topBoxConstraint = 0;
		this.getRelativeMousePos = ImageEditor.Resize.getRelativeMousePos.bind(this);
		this.enable = ImageEditor.Resize.enable.bind(this);
		this.disable = ImageEditor.Resize.disable.bind(this);
		var options = {
				resizeStop: ImageEditor.Resize.resizeStop.bind(this),
				onDrag: ImageEditor.Resize.onDrag.bind(this),
				onResize: ImageEditor.Resize.onResize.bind(this),
				getMousePos: ImageEditor.Resize.getMousePos.bind(this)
			};		
		new ImageEditor.Positioning.addBehaviour(this.element);
		this.imageContainerResize = new ImageEditor.Resizeable.initialize(element,options);
		this.imageContainerResize.setVisible(false);
		this.lastResize = {};
	},
	
	resizeStop: function(event) {
		if(ImageEditor.EventStack.getLastEventElement() != null) {
			var imageElement = $('image');
			ImageEditor.EventStack.clearStack();
			if(this.imageContainerResize.isEnabled) {
				if(this.imageContainerResize.originalWidth != imageElement.width || this.imageContainerResize.originalHeight != imageElement.height) {
					$('imageContainer').style.backgroundImage = 'url("")';
					this.lastResize.width = imageElement.width;
					this.lastResize.height = imageElement.height;
					ImageEditor.transformation.resize(imageElement.width,imageElement.height,ImageEditor.Resize.resizeCallback.bind(this));
				}	
			}
		}
	},
	
	resizeCallback: function() {
		$('imageContainer').style.backgroundImage = 'url("' + $('image').src + '")';
		ImageEditor.history.addResize($('image').src,this.lastResize.width,this.lastResize.height);
	},
	
	onDrag: function()
	{
		if(this.element.getTop() < this.topBoxConstraint) this.element.style.top = this.topBoxConstraint + "px";
		if(this.element.getLeft() < this.leftBoxConstraint) this.element.style.left = this.leftBoxConstraint + "px";
		ImageEditor.imageBox.reCenterIndicator();		
	},
	
 	onResize: function(width,height) {
		$('image').style.width = width + "px";
		$('image').style.height = height + "px"; 
	},
	getMousePos: function(event) {
		var relativeMouseX = this.getRelativeMousePos(event).x;
		var relativeMouseY = this.getRelativeMousePos(event).y;
		if(relativeMouseX <= this.leftBoxConstraint) x = this.leftBoxConstraint + this.element.getParentLeft(); else x = relativeMouseX + this.element.getParentLeft();
		if(relativeMouseY <= this.topBoxConstraint) y = this.topBoxConstraint + this.element.getParentTop(); else y = relativeMouseY + this.element.getParentTop();
		return {x: x,y: y};				
	},
	
	getRelativeMousePos: function(event) {
		var relativeMouseX = Event.pointerX(event) + $('imageEditorContainer').scrollLeft - this.element.getParentLeft();
		var relativeMouseY = Event.pointerY(event) + $('imageEditorContainer').scrollTop - this.element.getParentTop();
		return {x: relativeMouseX,y: relativeMouseY};				
	},
	
	enable: function() {
	   this.imageContainerResize.enable();
	},
	
	disable: function() {
	   this.imageContainerResize.disable();
	}
}