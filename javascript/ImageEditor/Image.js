/**
 * @author Mateusz
 */
var ImageToResize = {	
	initialize: function(imageFile) {
		Element.hide($('image'));
		this.imageToResize = $('image');
		this.imageToResize.src = imageFile;
		this.reportSize = ImageToResize.reportSize.bind(this);
		this.onImageLoad = ImageToResize.onImageLoad.bind(this);
		this.resizeOnFirstLoad = ImageToResize.resizeOnFirstLoad.bind(this);
		Event.observe(this.imageToResize,'load',this.onImageLoad);
	},
		
	reportSize: function(width,height) {
		if(width != null && height != null) {
			$('imageWidth').innerHTML = width + "px";
            $('imageHeight').innerHTML = height + "px";	
		} else {
            $('imageWidth').innerHTML = this.imageToResize.width + "px";
            $('imageHeight').innerHTML = this.imageToResize.height + "px";  
		}
	},
	
	onImageLoad: function(event) {
		if(this.imageToResize.width != 0 && this.imageToResize.height != 0) {
			$('imageContainer').style.backgroundImage = 'url("' + $('image').src + '")';
			imageBox.hideIndicator();
			Element.show($('imageContainer'),$('image'));
            if(resize.imageContainerResize.originalHeight == 0 && resize.imageContainerResize.originalWidth == 0) {
				imageHistory.add('initialize',$('image').src);
				this.resizeOnFirstLoad();
				imageBox.center();
	        }
			resize.imageContainerResize.originalWidth = this.imageToResize.width;
			resize.imageContainerResize.originalHeight = this.imageToResize.height;
			resize.imageContainerResize.placeClickBox();
			crop.enable();
			resize.imageContainerResize.enable();
			effects.enableRotate();
			imageHistory.enable();
			crop.onImageLoadCallback();
		}
		this.reportSize();
	},
	
	resizeOnFirstLoad: function() {	
	   var windowWidth = Element.getDimensions($('mainContainer')).width;
	   var windowHeight = Element.getDimensions($('mainContainer')).height - 100;
	   var imageWidth =  Element.getDimensions($('image')).width;
	   var imageHeight = Element.getDimensions($('image')).height;
	   if(imageWidth > windowWidth - 40 || imageHeight >  windowHeight - 40) {
		   imageHistory.clear();
		   Element.hide($('imageContainer'),$('image'));
		   ratio = imageWidth / imageHeight;
	       $('loadingIndicatorContainer2').style.left = windowWidth/2 + 'px';
	       $('loadingIndicatorContainer2').style.top = windowHeight/2 + 100 + 'px';
		   while(imageWidth > windowWidth - 40 || imageHeight >  windowHeight - 40) {
	           imageWidth--;
	           imageHeight = imageWidth * (1/ratio);
	       }
	       this.reportSize(0,0);
	       resize.imageContainerResize.setVisible(false);
	       imageTransformation.resize(imageWidth,imageHeight,ImageToResize.resizeOnFirstLoadCallBack.bind(this),false);
	   }	
    },
    
    resizeOnFirstLoadCallBack: function() {
        Element.hide($('loadingIndicatorContainer2'));
		resize.imageContainerResize.setVisible(true);
		resize.imageContainerResize.placeClickBox();
        imageBox.center();
    }
};
