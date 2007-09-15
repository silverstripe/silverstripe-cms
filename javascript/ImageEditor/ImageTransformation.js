/**
 * @author Mateusz
 */
var ImageTransformation = {
	initialize: function() {
		this.resize = ImageTransformation.resize.bind(this);
		this.rotate = ImageTransformation.rotate.bind(this);
		this.crop = ImageTransformation.crop.bind(this);	
		this.save = ImageTransformation.save.bind(this);
		this.close = ImageTransformation.close.bind(this);
	},
		
	resize: function(width,height,callback) {
		if(imageHistory.modifiedOriginalImage) {
			fileToResize = $('image').src;
		} else {
			fileToResize = imageEditor.originalImageFile;
		}	
		var options = {
		 	method: 'post',
			postBody: 'command=resize&file=' + fileToResize + '&newImageWidth=' + width + '&newImageHeight=' + height,
			onSuccess: function(transport) {
				imageBox.hideIndicator();
				response = eval('(' + transport.responseText + ')');
				$('image').src = response.fileName;
				$('image').style.width = response.width + 'px';
                $('image').style.height = response.height + 'px';
                $('imageContainer').style.width = response.width + 'px';
                $('imageContainer').style.height = response.height + 'px';
				imageHistory.add('resize',$('image').src);
				if(callback != null) callback();
			}
		 };
		 imageBox.showIndicator();
		 new Ajax.Request('admin/ImageEditor/manipulate', options);
	},
	
	rotate: function(angle,callback) {
		var options = {
		 	method: 'post',
			postBody: 'command=rotate&file=' + $('image').src + '&angle=' + angle ,
			onSuccess: function(transport) {
				imageBox.hideIndicator();
				response = eval('(' + transport.responseText + ')');
				imageBox.checkOutOfDrawingArea(response.width,response.height);
				$('image').src = response.fileName;
				$('image').style.width = response.width + 'px';
				$('image').style.height = response.height + 'px';
				$('imageContainer').style.width = response.width + 'px';
				$('imageContainer').style.height = response.height + 'px';
				imageHistory.add('rotate',$('image').src);	
				resize.imageContainerResize.placeClickBox();
				if(callback != null) callback();			
			}			
		 };
		 imageBox.showIndicator();

		 new Ajax.Request('admin/ImageEditor/manipulate', options);		
	},
	
	crop: function(top,left,width,height,callback) {
		var options = {
		 	method: 'post',
			postBody: 'command=crop&file=' + $('image').src + '&top=' + top + '&left=' + left + '&width=' + width + '&height=' + height,
			onSuccess: function(transport) {
				imageBox.hideIndicator();
				response = eval('(' + transport.responseText + ')');
				$('image').src = response.fileName;
				$('image').style.width = response.width + 'px';
				$('image').style.height = response.height + 'px';
				$('imageContainer').style.width = response.width + 'px';
				$('imageContainer').style.height = response.height + 'px';
				imageHistory.add('crop',$('image').src);	
				crop.setVisible(false);
				if(callback != null) callback();    
			}
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
	}
}
	
