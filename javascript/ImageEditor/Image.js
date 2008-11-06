/**
 * @author Mateusz
 */
ImageEditor.ImageToResize = {	
	initialize: function(imageFile) {
		Element.hide($('image'));
		this.imageToResize = $('image');
		this.imageToResize.src = imageFile;
		this.reportSize = ImageEditor.ImageToResize.reportSize.bind(this);
		this.onImageLoad = ImageEditor.ImageToResize.onImageLoad.bind(this);
		this.resizeOnFirstLoad = ImageEditor.ImageToResize.resizeOnFirstLoad.bind(this);
		Event.observe(this.imageToResize,'load',this.onImageLoad);
		this.firstResize = {};
	},
		
	reportSize: function(width,height) {
		if(width != null && height != null) {
			$('ImageWidth').innerHTML = width + "px";
            $('ImageHeight').innerHTML = height + "px";	
		} else {
            $('ImageWidth').innerHTML = this.imageToResize.width + "px";
            $('ImageHeight').innerHTML = this.imageToResize.height + "px";  
		}
	},
	
	onImageLoad: function(event) {
		if(this.imageToResize.width != 0 && this.imageToResize.height != 0) {
			$('imageContainer').style.backgroundImage = 'url("' + $('image').src + '")';
			ImageEditor.imageBox.hideIndicator();
			Element.show($('imageContainer'),$('image'));
            if(ImageEditor.resize.imageContainerResize.originalHeight == 0 && ImageEditor.resize.imageContainerResize.originalWidth == 0) {
				ImageEditor.history.add('initialize',$('image').src);
				this.resizeOnFirstLoad();
				ImageEditor.imageBox.center();
	        }
			ImageEditor.resize.imageContainerResize.originalWidth = this.imageToResize.width;
			ImageEditor.resize.imageContainerResize.originalHeight = this.imageToResize.height;
			ImageEditor.resize.imageContainerResize.placeClickBox();
			ImageEditor.crop.onImageLoadCallback();
		}
		this.reportSize();
	},
	
	resizeOnFirstLoad: function() {	
	   var windowWidth = Element.getDimensions($('Main')).width;
	   var windowHeight = Element.getDimensions($('Main')).height - 100;
	   var imageWidth =  Element.getDimensions($('image')).width;
	   var imageHeight = Element.getDimensions($('image')).height;
	   if(imageWidth > windowWidth - 40 || imageHeight >  windowHeight - 40) {
		   ImageEditor.history.clear();
		   Element.hide($('imageContainer'),$('image'));
		   var ratio = imageWidth / imageHeight;
	       $('loadingIndicatorContainer2').style.left = windowWidth/2 + 'px';
	       $('loadingIndicatorContainer2').style.top = windowHeight/2 + 100 + 'px';
		   while(imageWidth > windowWidth - 40 || imageHeight >  windowHeight - 40) {
	           imageWidth--;
	           imageHeight = imageWidth * (1/ratio);
	       }
	       this.reportSize(0,0);
	       ImageEditor.resize.imageContainerResize.setVisible(false);
	       ImageEditor.transformation.resize(imageWidth,imageHeight,ImageEditor.ImageToResize.resizeOnFirstLoadCallBack.bind(this),false);
	       this.firstResize.width = imageWidth;
           this.firstResize.height = imageHeight;
	   }	
    },
    
    resizeOnFirstLoadCallBack: function() {
        ImageEditor.history.addResize($('image').src,this.firstResize.width,this.firstResize.height);
        Element.hide($('loadingIndicatorContainer2'));
		ImageEditor.resize.imageContainerResize.setVisible(true);
		ImageEditor.resize.imageContainerResize.placeClickBox();
        ImageEditor.imageBox.center();
    }
};
