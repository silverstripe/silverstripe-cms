/**
 * @author Mateusz
 */
var Effects = {
	initialize: function() {
		this.setListeners = Effects.setListeners.bind(this);
		this.rotate = Effects.rotate.bind(this);
		this.setListeners();
		this.isRotateEnabled = true; 	
		this.enableRotate = Effects.enableRotate.bind(this);
		this.disableRotate = Effects.disableRotate.bind(this);
	},
	
	rotate: function() {
		if(this.isRotateEnabled) {
			resize.imageContainerResize.disable();
			crop.disable();
			imageHistory.disable();
			imageTransformation.rotate(90,Effects.rotateCallback.bind(this));
			this.isRotateEnabled = false;
		}
	},
	
	rotateCallback: function() {
	   resize.imageContainerResize.placeClickBox();
	   this.isRotateEnabled = true;
	},
	
	setListeners: function() {
		Event.observe('rotateButton','click',this.rotate);
	},
	
	disableRotate: function() {
	   this.isRotateEnabled = false;   
	},
	
	enableRotate: function() {
	    this.isRotateEnabled = true;
	}
		
}