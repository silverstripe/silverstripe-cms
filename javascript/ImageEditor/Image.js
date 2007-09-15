/**
 * @author Mateusz
 */
var ImageToResize = {	
	initialize: function(imageFile) {
		Element.hide($('image'));
		this.image = $('image');
		this.image.src = imageFile;
		this.reportSize = ImageToResize.reportSize.bind(this);
		this.onImageLoad = ImageToResize.onImageLoad.bind(this);
		this.resizeOnFirstLoad = ImageToResize.resizeOnFirstLoad.bind(this);
		Event.observe(this.image,'load',this.onImageLoad);
		imageHistory.add('initialize',this.image.src);
		
	},
	
	reportSize: function(width,height) {
		if(width != null && height != null) {
			$('imageWidth').innerHTML = width + "px";
            $('imageHeight').innerHTML = height + "px";	
		} else {
            $('imageWidth').innerHTML = this.image.width + "px";
            $('imageHeight').innerHTML = this.image.height + "px";  
		}
	},
	
	onImageLoad: function(event) {
		this.reportSize();
		$('imageContainer').style.width = this.image.width + 'px';
		$('imageContainer').style.height = this.image.height + 'px';
		if(resize.imageContainerResize.originalHeight == 0 && resize.imageContainerResize.originalWidth == 0) {
			imageBox.center();
			this.resizeOnFirstLoad();
		}
		resize.imageContainerResize.originalWidth = this.image.width;
		resize.imageContainerResize.originalHeight = this.image.height;
		imageBox.checkOutOfDrawingArea($('imageContainer').getWidth(),$('imageContainer').getHeight());
	},
	
	resizeOnFirstLoad: function() {
	   windowWidth = Element.getDimensions($('mainContainer')).width;
	   windowHeight = Element.getDimensions($('mainContainer')).height;
	   imageWidth =  Element.getDimensions(this.image).width;
	   imageHeight= Element.getDimensions(this.image).height;
	   if(imageWidth > windowWidth || imageHeight > windowHeight) {
	       ratio = imageWidth / imageHeight;
	       if(imageWidth > imageHeight) {
                newWidth = windowWidth - windowWidth/1.75;
                newHeight = newWidth * (1/ratio);
	       } else {
                newHeight = windowHeight - windowHeight/1.75;
                newWidth = newHeight * ratio;	       
	       }
	       this.reportSize(0,0);
           imageHistory.clear();
	       imageTransformation.resize(newWidth,newHeight,ImageToResize.resizeOnFirstLoadCallBack.bind(this));
	   } else {
	       if(imageWidth != 0 && imageHeight != 0) Element.show($('image'));
	   }	
    },
    
    resizeOnFirstLoadCallBack: function() {
        imageBox.center();
        Element.show($('image'));
        resize.imageContainerResize.setVisible(true);
    }
};
