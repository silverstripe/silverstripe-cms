/**
 * @author Mateusz
 */
var ImageTransformation = {
	initialize: function() {
		this.currentOperation = "";
		this.currentResponse = new Array();
		this.currentCallback = null;
		this.resize = ImageTransformation.resize.bind(this);
		this.rotate = ImageTransformation.rotate.bind(this);
		this.crop = ImageTransformation.crop.bind(this);	
		this.save = ImageTransformation.save.bind(this);
		this.close = ImageTransformation.close.bind(this);
		this.onSuccess = ImageTransformation.onSuccess.bind(this);
		this.onImageLoad = ImageTransformation.onImageLoad.bind(this);
	},
		
	resize: function(width,height,callback,imageAlreadyChangedSize) {
		this.currentOperation = "resize";
		this.currentCallback = callback;	
		if(imageHistory.modifiedOriginalImage) {
			fileToResize = $('image').src;
		} else {
			fileToResize = imageEditor.originalImageFile;
		}	
		var options = {
		 	method: 'post',
			postBody: 'command=resize&file=' + fileToResize + '&newImageWidth=' + width + '&newImageHeight=' + height,
			onSuccess: this.onSuccess
		};
			
		 if(imageAlreadyChangedSize == false) {
			 imageBox.showIndicator($('mainContainer'));
	     } else {
			 imageBox.showIndicator();
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
		 imageBox.showIndicator();
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
		 imageBox.showIndicator();
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
		imageBox.hideIndicator();			
		resize.imageContainerResize.originalWidth = this.currentResponse.width;
		resize.imageContainerResize.originalHeight = this.currentResponse.height;
		$('imageContainer').style.height = this.currentResponse.height + 'px';
        $('imageContainer').style.width = this.currentResponse.width + 'px';
		$('image').style.height = this.currentResponse.height + 'px';
        $('image').style.width = this.currentResponse.width + 'px';
		imageHistory.add(this.currentOperation,$('image').src);
		if(this.currentCallback != null) this.currentCallback();
	}
}
	
