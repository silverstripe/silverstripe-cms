/**
 * @author Mateusz
 */
ImageEditor.ImageTransformation = {
	initialize: function() {
		this.currentOperation = "";
		this.currentResponse = new Array();
		this.currentCallback = null;
		this.resize = ImageEditor.ImageTransformation.resize.bind(this);
		this.rotate = ImageEditor.ImageTransformation.rotate.bind(this);
		this.crop = ImageEditor.ImageTransformation.crop.bind(this);	
		this.save = ImageEditor.ImageTransformation.save.bind(this);
		this.close = ImageEditor.ImageTransformation.close.bind(this);
		this.onSuccess = ImageEditor.ImageTransformation.onSuccess.bind(this);
		this.onImageLoad = ImageEditor.ImageTransformation.onImageLoad.bind(this);
	},
		
	resize: function(width,height,callback,imageAlreadyChangedSize) {
		this.currentOperation = "resize";
		this.currentCallback = callback;	
		if(ImageEditor.imageHistory.modifiedOriginalImage) {
			var fileToResize = $('image').src;
		} else {
			var fileToResize = ImageEditor.imageEditor.originalImageFile;
		}	
		var options = {
		 	method: 'post',
			postBody: 'command=resize&file=' + fileToResize + '&newImageWidth=' + width + '&newImageHeight=' + height,
			onSuccess: this.onSuccess
		};
			
		 if(imageAlreadyChangedSize == false) {
			 ImageEditor.imageBox.showIndicator($('Main'));
	     } else {
			 ImageEditor.imageBox.showIndicator();
		 }
		 new Ajax.Request('admin/ImageEditor/manipulate', options);
	},
	
	rotate: function(angle,callback) {
		this.currentOperation = "rotate";
		this.currentCallback = callback;
		var options = {
		 	method: 'post',
			postBody: 'command=rotate&file=' + $('image').src + '&angle=' + angle ,
			onSuccess: this.onSuccess
		 };
		 ImageEditor.imageBox.showIndicator();
		 new Ajax.Request('admin/ImageEditor/manipulate', options);		
	},
	
	crop: function(top,left,width,height,callback) {
		this.currentOperation = "crop";
		this.currentCallback = callback;
		var options = {
		 	method: 'post',
			postBody: 'command=crop&file=' + $('image').src + '&top=' + top + '&left=' + left + '&width=' + width + '&height=' + height,
			onSuccess: this.onSuccess
		 };
		 ImageEditor.imageBox.showIndicator();
		 new Ajax.Request('admin/ImageEditor/manipulate', options);			
	},
	
	save: function(originalFile,editedFile,callback) {
		var options = {
		 	method: 'post',
			postBody: 'command=save&editedFile=' + editedFile + '&originalFile=' + originalFile,
			onSuccess: function(transport) {
				eval(transport.responseText);
				callback();
			}
		 };
		 new Ajax.Request('admin/ImageEditor/save', options);
	},
	
	close: function(callback) {
		var options = {
		 	method: 'post',
			postBody: '',
			onSuccess: function(transport) {
				eval(transport.responseText);
				callback();
			}
		 };
		 new Ajax.Request('admin/ImageEditor/close', options);
	},
	
	onSuccess: function(transport) {
		this.currentResponse = eval('(' + transport.responseText + ')');
		$('fakeImg').src = this.currentResponse.fileName;
		Event.observe('fakeImg','load',this.onImageLoad);
	},
	
	onImageLoad: function(event) {
		Event.stopObserving('fakeImg','load', this.onImageLoad);	
		$('image').src = this.currentResponse.fileName;
		ImageEditor.imageBox.hideIndicator();			
		ImageEditor.resize.imageContainerResize.originalWidth = this.currentResponse.width;
		ImageEditor.resize.imageContainerResize.originalHeight = this.currentResponse.height;
		$('imageContainer').style.height = this.currentResponse.height + 'px';
        $('imageContainer').style.width = this.currentResponse.width + 'px';
		$('image').style.height = this.currentResponse.height + 'px';
        $('image').style.width = this.currentResponse.width + 'px';
		ImageEditor.imageHistory.add(this.currentOperation,$('image').src);
		if(this.currentCallback != null) this.currentCallback();
	}
}
	
