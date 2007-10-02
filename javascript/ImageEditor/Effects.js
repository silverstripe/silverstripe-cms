/**
 * @author Mateusz
 */
ImageEditor.Effects = {
	initialize: function() {
		this.setListeners = ImageEditor.Effects.setListeners.bind(this);
		this.rotate = ImageEditor.Effects.rotate.bind(this);
		this.setListeners();
		this.isRotateEnabled = true; 	
		this.enableRotate = ImageEditor.Effects.enableRotate.bind(this);
		this.disableRotate = ImageEditor.Effects.disableRotate.bind(this);
	},
	
	rotate: function() {
		if(this.isRotateEnabled) {
			ImageEditor.resize.imageContainerResize.disable();
			ImageEditor.crop.disable();
			ImageEditor.imageHistory.disable();
			ImageEditor.imageTransformation.rotate(90,ImageEditor.Effects.rotateCallback.bind(this));
			this.isRotateEnabled = false;
		}
	},
	
	rotateCallback: function() {
	   ImageEditor.resize.imageContainerResize.placeClickBox();
	   this.isRotateEnabled = true;
	},
	
	setListeners: function() {
		Event.observe('RotateButton','click',this.rotate);
	},
	
	disableRotate: function() {
	   this.isRotateEnabled = false;   
	},
	
	enableRotate: function() {
	    this.isRotateEnabled = true;
	}
		
}