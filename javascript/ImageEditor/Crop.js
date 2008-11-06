/**
 * @author Mateusz
 */
ImageEditor.Crop = {
	
	initialize: function() {
		this.cropBox = $('cropBox');
		new ImageEditor.Positioning.addBehaviour(this.cropBox);
		this.imageContainer = $('imageContainer');
		this.leftGreyBox = $('leftGreyBox');
		this.rightGreyBox = $('rightGreyBox');
		this.upperGreyBox = $('upperGreyBox');
		this.lowerGreyBox = $('lowerGreyBox');		
		this.centerCropBox = ImageEditor.Crop.centerCropBox.bind(this);
		this.placeGreyBox = ImageEditor.Crop.placeGreyBox.bind(this);
		this.setListeners = ImageEditor.Crop.setListeners.bind(this);
		this.onCropStart = ImageEditor.Crop.onCropStart.bind(this);
		this.onCropOk = ImageEditor.Crop.onCropOk.bind(this);
		this.onCropCancel = ImageEditor.Crop.onCropCancel.bind(this);
		this.doCrop = ImageEditor.Crop.doCrop.bind(this);
		this.setVisible = ImageEditor.Crop.setVisible.bind(this);
		this.enable = ImageEditor.Crop.enable.bind(this);
		this.disable = ImageEditor.Crop.disable.bind(this);
		this.onImageLoadCallback = ImageEditor.Crop.onImageLoadCallback.bind(this);
		Event.observe('image','load',this.centerCropBox);
		var options = {
			resizeStop: ImageEditor.Crop.resizeStop.bind(this),
			onDrag: ImageEditor.Crop.onDrag.bind(this),
			onResize: ImageEditor.Crop.onResize.bind(this),
			getMousePos: ImageEditor.Crop.getMousePos.bind(this)
		};	
		this.resizeCropBox = new ImageEditor.Resizeable.initialize(this.cropBox,options);
		Event.observe(this.cropBox,'dblclick',this.onCropOk.bind(this));
		this.setListeners();
		this.isVisible = false;
		this.setVisible(this.isVisible);
		this.isEnabled = true;
		this.lastCrop = {};
	},
	
	resizeStop: function(event) {
		if(this.isVisible) {
			ImageEditor.EventStack.clearStack();
			this.resizeCropBox.originalHeight = this.cropBox.getHeight();
			this.resizeCropBox.originalWidth = this.cropBox.getWidth();
		}
	},
	
	onDrag: function(event) {
		if(this.cropBox.getLeft() <= 0 ) this.cropBox.style.left = '0px';
		if(this.cropBox.getTop() <= 0 ) this.cropBox.style.top = '0px';
		if(this.cropBox.getLeft() + this.cropBox.getWidth() > this.cropBox.getParentWidth()) this.cropBox.style.left = this.cropBox.getParentWidth()- this.cropBox.getWidth() + 'px';
		if(this.cropBox.getTop() + this.cropBox.getHeight() > this.cropBox.getParentHeight()) this.cropBox.style.top = this.cropBox.getParentHeight() - this.cropBox.getHeight() + 'px';
		this.placeGreyBox(this.cropBox.getWidth(),this.cropBox.getHeight());
	},
	
	centerCropBox: function() {
		this.cropBox.style.width = this.cropBox.getParentWidth()/2 + 'px';
		this.cropBox.style.height = this.cropBox.getParentHeight()/2 + 'px';
		this.cropBox.style.left = (this.cropBox.getParentWidth() - this.cropBox.getWidth())/2 + "px";
		this.cropBox.style.top = (this.cropBox.getParentHeight() - this.cropBox.getHeight())/2 + "px";
		this.placeGreyBox(this.cropBox.getWidth(),this.cropBox.getHeight());
		this.leftBoxConstraint = this.cropBox.getParentLeft();
		this.topBoxConstraint = this.cropBox.getParentTop();
		this.rightBoxConstraint = this.cropBox.getParentLeft() + this.cropBox.getParentWidth();
		this.bottomBoxConstraint = this.cropBox.getParentTop() + this.cropBox.getParentHeight()-1;//hack without 1 doesn't work;
	},
	
	placeGreyBox: function(width,height) {
		if(this.isVisible) {
			this.lowerGreyBox.style.left = this.cropBox.getLeft()  + 'px';				
			this.lowerGreyBox.style.width = width + 'px';
			this.lowerGreyBox.style.height = this.cropBox.getParentHeight() - this.cropBox.getTop() - height + "px";
			this.lowerGreyBox.style.top = this.cropBox.getTop() + height + "px";
			this.leftGreyBox.style.width = this.cropBox.getLeft() + "px";		
			this.leftGreyBox.style.height = $('imageContainer').getHeight() + 'px';
			this.rightGreyBox.style.width = this.cropBox.getParentWidth() - this.cropBox.getLeft() - width + "px";
			this.rightGreyBox.style.height = $('imageContainer').getHeight() + 'px';
			this.rightGreyBox.style.left = this.cropBox.getLeft()  + width  + "px";
			this.upperGreyBox.style.width = width + 'px';
			this.upperGreyBox.style.left = this.cropBox.getLeft()  + 'px';				
			this.upperGreyBox.style.height = this.cropBox.getTop() + 'px';
			this.resizeCropBox.placeClickBox();
		}
	},
	
	onResize: function(width,height) {
		if(width + parseInt(this.cropBox.style.left) > Element.getDimensions(this.imageContainer).width) {
            this.cropBox.style.left = parseInt(this.cropBox.style.left) - Math.abs(Element.getDimensions(this.imageContainer).width - (width + parseInt(this.cropBox.style.left))) +  "px";    		
		}
		if(parseInt(this.cropBox.style.left) < 0) {
            this.cropBox.style.left = "0px";         
        }
        if(width > Element.getDimensions(this.imageContainer).width) {
            this.cropBox.style.width = Element.getDimensions(this.imageContainer).width + "px";
            width = Element.getDimensions(this.imageContainer).width;
        }
		this.placeGreyBox(width,height);
	},
	
	getMousePos: function(event) {
		var x = Event.pointerX(event) + $('imageEditorContainer').scrollLeft;
		var y = Event.pointerY(event) + $('imageEditorContainer').scrollTop;
		if(x <= this.leftBoxConstraint) x = this.leftBoxConstraint;
		if(y <= this.topBoxConstraint) y = this.topBoxConstraint;
		if(x >= this.rightBoxConstraint) x = this.rightBoxConstraint;
		if(y >= this.bottomBoxConstraint) y = this.bottomBoxConstraint;
		return {x: x,y: y};		
	},
	
	doCrop: function() {
		if(this.isEnabled) {
			var newWidth = this.cropBox.getWidth() 
			var newHeight = this.cropBox.getHeight() ;
			var startTop = this.cropBox.getTop() ;
			var startLeft = this.cropBox.getLeft() ;
			if(newWidth > 35 && newHeight > 35) {
				this.lastCrop.top = startTop;
				this.lastCrop.left = startLeft;
				this.lastCrop.newWidth = newWidth;
				this.lastCrop.newHeight = newHeight;
				ImageEditor.transformation.crop(startTop,startLeft,newWidth,newHeight,ImageEditor.Crop.cropCallback.bind(this));
				this.disable();
			} else {
				ImageEditor.statusMessageWrapper.statusMessage("Crop area too small","bad");
				return false;
			}
			$('image').style.visibility = 'visible';//hack for IE for not selecting image during crop
			return true;
		}
	},
	
	cropCallback: function() {
	   ImageEditor.history.addCrop($('image').src,
	                                               this.lastCrop.top,
	                                               this.lastCrop.left,
	                                               this.lastCrop.newWidth,
	                                               this.lastCrop.newHeight
	                               );
	   ImageEditor.resize.imageContainerResize.placeClickBox();
   	   ImageEditor.resize.imageContainerResize.setVisible(true);
	   Element.show($('CropText'));
	   Element.hide(this.cropBox,this.leftGreyBox,this.rightGreyBox,this.upperGreyBox,this.lowerGreyBox,$('CurrentAction'));							
	},
	
	setListeners: function() {
		Event.observe('CropButton','click',this.onCropStart);
		Event.observe('CancelButton','click',this.onCropCancel);
		Event.observe('ApplyButton','click',this.onCropOk);
	},
	onCropStart: function()	 {
		if(this.isEnabled) {
			$('image').style.visibility = "hidden";//hack for IE for not selecting image during crop
			this.setVisible(true);	
			Element.show($('CurrentAction'));
			ImageEditor.Main.disableFunctionality();
			this.enable();
		}
	},
	
	onCropOk: function() {
		if(this.isEnabled) {
		    if(this.doCrop()) Element.hide($('CurrentAction'));
		}	
	},
	
	onCropCancel: function(event) {
		if(this.isEnabled) {
		    Element.hide($('CurrentAction'));
		    Element.show($('CropText'));
		    this.setVisible(false);
		    ImageEditor.Main.enableFunctionality();
			this.enable();
		}
		$('image').style.visibility = 'visible';//hack for IE for not selecting image during crop
	},
	
	setVisible: function(setVisible) {
		this.isVisible = setVisible;
		if(setVisible) {
			Element.show(this.cropBox,this.leftGreyBox,this.rightGreyBox,this.upperGreyBox,this.lowerGreyBox);			
			this.centerCropBox();
			this.placeGreyBox(this.cropBox.getWidth(),this.cropBox.getHeight());
		} else {
			Element.hide(this.cropBox,this.leftGreyBox,this.rightGreyBox,this.upperGreyBox,this.lowerGreyBox,$('CurrentAction'));							
		}
		ImageEditor.resize.imageContainerResize.setVisible(!setVisible);
		this.resizeCropBox.setVisible(setVisible);
	},
	
	enable: function() {
	   this.isEnabled = true;
	},
	
	disable: function() {
	   this.isEnabled = false;
	},
	
	onImageLoadCallback: function() {
		ImageEditor.crop.setVisible(false);	
	}
	
}