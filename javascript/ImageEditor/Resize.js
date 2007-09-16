/**
 * @author Mateusz
 */
var Resize = {
	
	initialize: function(element) {		
		this.element = element;
		this.leftBoxConstraint = 4;
		this.topBoxConstraint = 4;
		this.getRelativeMousePos = Resize.getRelativeMousePos.bind(this);
		options = {
				resizeStop: Resize.resizeStop.bind(this),
				onDrag: Resize.onDrag.bind(this),
				onResize: Resize.onResize.bind(this),
				getMousePos: Resize.getMousePos.bind(this)
			};		
		new Positioning.addBehaviour(this.element);
		this.imageContainerResize = new Resizeable.initialize(element,options);
		this.imageContainerResize.setVisible(false);
	},
	
	resizeStop: function(event) {
		if(EventStack.getLastEventElement() != null) {
			imageElement = $('image');
			EventStack.clearStack();
			if(this.imageContainerResize.isEnabled) {
				if(this.imageContainerResize.originalWidth != imageElement.width || this.imageContainerResize.originalHeight != imageElement.height) {
					$('imageContainer').style.backgroundImage = 'url("")';
					imageTransformation.resize(imageElement.width,imageElement.height,Resize.resizeCallback.bind(this));
					effects.disableRotate();
					crop.disable();
					this.imageContainerResize.disable();
					imageHistory.disable();
				}	
			}
		}
	},
	
	resizeCallback: function() {
		$('imageContainer').style.backgroundImage = 'url("' + $('image').src + '")';
	},
	
	onDrag: function()
	{
		if(this.element.getTop() < this.topBoxConstraint) this.element.style.top = this.topBoxConstraint + "px";
		if(this.element.getLeft() < this.leftBoxConstraint) this.element.style.left = this.leftBoxConstraint + "px";
		imageBox.reCenterIndicator();		
	},
	
 	onResize: function(width,height) {
		$('image').style.width = width + "px";
		$('image').style.height = height + "px"; 
	},
	getMousePos: function(event) {
		relativeMouseX = this.getRelativeMousePos(event).x;
		relativeMouseY = this.getRelativeMousePos(event).y;
		if(relativeMouseX <= this.leftBoxConstraint) x = this.leftBoxConstraint + this.element.getParentLeft(); else x = relativeMouseX + this.element.getParentLeft();
		if(relativeMouseY <= this.topBoxConstraint) y = this.topBoxConstraint + this.element.getParentTop(); else y = relativeMouseY + this.element.getParentTop();
		return {x: x,y: y};				
	},
	
	getRelativeMousePos: function(event) {
		relativeMouseX = Event.pointerX(event) + $('imageEditorContainer').scrollLeft - this.element.getParentLeft();
		relativeMouseY = Event.pointerY(event) + $('imageEditorContainer').scrollTop - this.element.getParentTop();
		return {x: relativeMouseX,y: relativeMouseY};				
	}
}