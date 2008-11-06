ImageEditor.Transformation = {
	initialize: function() {
		this.currentOperation = "";
		this.currentResponse = new Array();
		this.currentCallback = null;
		this.queue = new Array();
		this.resize = ImageEditor.Transformation.resize.bind(this);
		this.customRequest = ImageEditor.Transformation.customRequest.bind(this);
		this.crop = ImageEditor.Transformation.crop.bind(this);
		this.save = ImageEditor.Transformation.save.bind(this);
		this.close = ImageEditor.Transformation.close.bind(this);
		this.onSuccess = ImageEditor.Transformation.onSuccess.bind(this);
		this.onImageLoad = ImageEditor.Transformation.onImageLoad.bind(this);
		this.getOptions = ImageEditor.Transformation.getOptions.bind(this);
		this.applyHistory = ImageEditor.Transformation.applyHistory.bind(this);
		this.processLastOperationFromQueue = ImageEditor.Transformation.processLastOperationFromQueue.bind(this);
		this.addToQueueBeginning = ImageEditor.Transformation.addToQueueBeginning.bind(this);
		this.removeLastElementFromQueue = ImageEditor.Transformation.removeLastElementFromQueue.bind(this);
	},
	
    resize: function(width,height,callback,imageAlreadyChangedSize,file) {
		this.currentOperation = "resize";
		this.currentCallback = callback;	
		if(ImageEditor.history.onlyResized()) {
			var fileToResize = ImageEditor.imageEditor.originalImageFile;
	} else {
			var fileToResize = $('image').src;
		}	
		options = this.getOptions('command=resize&file=' + (file != undefined ? file : fileToResize) + '&newImageWidth=' + width + '&newImageHeight=' + height)
		if(imageAlreadyChangedSize == false) {
		    ImageEditor.imageBox.showIndicator($('Main'));
	    } else {
		    ImageEditor.imageBox.showIndicator();
		}
		ImageEditor.Main.disableFunctionality();
		return new Ajax.Request('admin/ImageEditor/manipulate', options);
},
	
	customRequest: function(name,callback,file,value,addToQueue) {
        if(this.queue.length > 0 && addToQueue) {
            this.addToQueueBeginning({'operation' : name,'callback' : callback,'additionalInfo': {'value' : value}});     
        } else {
	        this.currentOperation = name;
        this.currentCallback = callback;
	        var options = this.getOptions(  'command=' + name + 
	                                        '&file=' + (file != undefined ? file : $('image').src) + 
	                                        (value != undefined ? ('&value=' + value) : '')
	                                      );
	        ImageEditor.imageBox.showIndicator();
	        ImageEditor.Main.disableFunctionality();
	        return new Ajax.Request('admin/ImageEditor/manipulate', options);
	   }
    },
    
	crop: function(top,left,width,height,callback,file) {
		this.currentOperation = "crop";
		this.currentCallback = callback;
		var options = this.getOptions('command=crop&file=' + (file != undefined ? file : $('image').src) + '&top=' + top + '&left=' + left + '&width=' + width + '&height=' + height);
		ImageEditor.imageBox.showIndicator();
		ImageEditor.Main.disableFunctionality();
		return new Ajax.Request('admin/ImageEditor/manipulate', options);	
	},
	save: function(originalFile,editedFile,callback) {
		var options = this.getOptions('command=save&editedFile=' + editedFile + '&originalFile=' + originalFile);
        options.onSuccess = function(transport) {
                                eval(transport.responseText);
                                callback();
                           }
		new Ajax.Request('admin/ImageEditor/save', options);
	},
	
	close: function(callback) {
		var options = this.getOptions('');
        options.onSuccess = function(transport) {
				eval(transport.responseText);
			callback();
			}
		 new Ajax.Request('admin/ImageEditor/close', options);
	},
	
onSuccess: function(transport) {
		this.currentResponse = eval('(' + transport.responseText + ')');
		if(this.queue.length > 0) this.removeLastElementFromQueue();
		if(this.queue.length > 0) {
		    this.processLastOperationFromQueue('http://' + location.host + '/' + this.currentResponse.fileName);
		} else {
		    $('fakeImg').src = this.currentResponse.fileName;
            Event.observe('fakeImg','load',this.onImageLoad);
		}
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
		ImageEditor.resize.imageContainerResize.placeClickBox();
	if(this.queue.length == 0) ImageEditor.Main.enableFunctionality();
		if(this.currentCallback != null) this.currentCallback();
	},
	
	getOptions: function(postBodyContent) {
  return options = {
          asynchronous: true,
            method: 'post',
            postBody: postBodyContent,
            onSuccess: this.onSuccess
        };
	},
	
applyHistory: function(history) {
        for(var i=1;history[i] != undefined;i++) {
            this.addToQueueBeginning(history[i]);
        }
        this.processLastOperationFromQueue(history[0].fileUrl);
	},

	addToQueueBeginning: function(historyEntry) {
	    if(this.queue.length == 0) {
            this.queue[0] = historyEntry;	    
	    } else {
		    for(var i=this.queue.length-1;i>=0;i--) {
		        this.queue[i+1] = this.queue[i];  
	    }
		    this.queue[0] = historyEntry;
		}
	},
	
	processLastOperationFromQueue: function(file) {
  		o = this.queue[this.queue.length-1];
      	switch(o.operation) {
           case 'resize':
               ImageEditor.transformation.resize(o.additionalInfo.width,o.additionalInfo.height,function() {},true,file);
           break;
           case 'crop':
               ImageEditor.transformation.crop(o.additionalInfo.top,o.additionalInfo.left,o.additionalInfo.width,o.additionalInfo.height,function() {},file);
           break;
          default:
              var value = '';
               var callback = function() {};
              if(o.additionalInfo != undefined && o.additionalInfo.value != undefined) value = o.additionalInfo.value; 
               if(o.callback != undefined) callback = o.callback;
               ImageEditor.transformation.customRequest(o.operation,callback,file,value,false);
           break;
       }
	},
	
	removeLastElementFromQueue: function() {
	    this.queue = this.queue.slice(0,this.queue.length-1);
}
}