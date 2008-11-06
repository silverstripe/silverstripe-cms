/**
 * @author Mateusz
 */
ImageEditor.History = {
	
	initialize: function() {
		this.history = new Array();
		this.historyPointer = -1;
		this.isEnabled = true;
		this.image = ImageEditor.Positioning.addBehaviour($('image'));
		this.size = new Array();
		this.fakeImage = $('fakeImg');
		this.image = $('image');
		this.adjust = new Array();
		this.undo = ImageEditor.History.undo.bind(this);
		this.redo = ImageEditor.History.redo.bind(this);
		this.add = ImageEditor.History.add.bind(this);
		this.addListeners = ImageEditor.History.addListeners.bind(this);
		this.hasOperation = ImageEditor.History.hasOperation.bind(this);		
		this.isInHistory = ImageEditor.History.isInHistory.bind(this);
		this.onImageLoad = ImageEditor.History.onImageLoad.bind(this);
		this.removeLastOperation = ImageEditor.History.removeLastOperation.bind(this);
		this.getOptimizedHistory = ImageEditor.History.getOptimizedHistory.bind(this);
		this.addCrop = ImageEditor.History.addCrop.bind(this);
        this.addResize = ImageEditor.History.addResize.bind(this);
        this.addEffect = ImageEditor.History.addEffect.bind(this);
        this.addAdjust = ImageEditor.History.addAdjust.bind(this);
		this.enable = ImageEditor.History.enable.bind(this);
		this.disable = ImageEditor.History.disable.bind(this);
		this.clear = ImageEditor.History.clear.bind(this);
		this.onlyResized = ImageEditor.History.onlyResized.bind(this);
		this.optimizeOtherEffects = ImageEditor.History.optimizeOtherEffects.bind(this);
		this.optimizeCrop = ImageEditor.History.optimizeCrop.bind(this);
		this.optimizeResize = ImageEditor.History.optimizeResize.bind(this);
		this.optimizeRotate = ImageEditor.History.optimizeRotate.bind(this);
		this.checkSpecialOperation = ImageEditor.History.checkSpecialOperation.bind(this);
		this.addListeners();
	},
		
	undo: function() {
		if(this.isEnabled) {
			if(this.historyPointer >= 1) {
				var operation = this.history[this.historyPointer].operation;
				this.checkSpecialOperation('undo',this.history[this.historyPointer]);
				Event.observe('image','load',this.onImageLoad);
				this.historyPointer = this.historyPointer - 1;
				this.image.src = this.history[this.historyPointer].fileUrl;
			} else {
				ImageEditor.statusMessageWrapper.statusMessage("No more undo","bad");
			}
		}
	},
	
	redo: function() {
		if(this.isEnabled) {
			if(this.historyPointer < this.history.length-1) {
				var operation = this.history[this.historyPointer+1].operation;
				this.checkSpecialOperation('redo',this.history[this.historyPointer+1]);
				Event.observe('image','load',this.onImageLoad);
				this.historyPointer = this.historyPointer + 1;
				this.image.src = this.history[this.historyPointer].fileUrl;
			} else {
				ImageEditor.statusMessageWrapper.statusMessage("No more redo","bad");
			}
		}
	},
	
	add: function(operation,url,additionalInfo) {
		var imageWidth =  isNaN(parseInt($('image').style.width)) ? Element.getDimensions($('image')).width : parseInt($('image').style.width);//IE hack
		var imageHeight = isNaN(parseInt($('image').style.height)) ? Element.getDimensions($('image')).height : parseInt($('image').style.height);//IE hack
		//code above should be moved to Positioning.addBehaviour
		if(!this.isInHistory(operation,url)) {
			this.historyPointer++;
			this.size[this.historyPointer] = {'width': imageWidth,'height': imageHeight};
			this.history[this.historyPointer] = {'operation': operation,'fileUrl' : url,'additionalInfo': additionalInfo};
			this.size = this.size.slice(0,this.historyPointer+1);
			this.history = this.history.slice(0,this.historyPointer+1);
		}
	},
	
	addCrop: function(url,top,left,width,height) {
	   this.add('crop',url,{
	                        'top':top,
	                        'left': left,
	                        'width': width,
	                        'height': height
	                       });
	},
	
	addResize: function(url,width,height) {
	   this.add('resize',url,{
                            'width': width,
                            'height': height
                           });
	},
	
	addEffect: function(url,name) {
	   this.add(name,url);
	},
	
	addAdjust: function(name,value,url) {
	   this.add(name,url,{'value': value});
       if(this.adjust[name] == undefined) {
           this.adjust[name] = {'pointer': 0,'values': Array()}
           this.adjust[name].values[0] = ImageEditor.effects.getEffect(name).getDefaultValue();
       }
       this.adjust[name].values[this.adjust[name].values.length] = value;
       this.adjust[name].pointer++;
	},
	
	addListeners: function() {
		this.undoListener = Event.observe('UndoButton','click',this.undo);	
		this.redoListener = Event.observe('RedoButton','click',this.redo);
	},
	
	hasOperation: function(operation,historyPointer) {
		if(historyPointer == undefined) historyPointer = this.history.length-1;
		for(i=historyPointer;i>=0;i--) {
			if(this.history[i].operation == operation) {
				return true;
			}
		}
		return false;
	},
	
	getOptimizedHistory: function(without) {
	   var history = this.history.slice(0,this.historyPointer+1);
	   var result = {};
	   var historyPointer = 1;
	   result[0] = {fileUrl : history[0].fileUrl};
	   var resize = this.optimizeResize(history,this.size);
	   var rotate = this.optimizeRotate(history);
	   var crop = this.optimizeCrop(history,this.size);
	   var other = this.optimizeOtherEffects(history,without);
       if(rotate != undefined) {
           for(var i =0;i<rotate.length;i++) {
               result[historyPointer] = rotate[i];
               historyPointer++;               
           }
       }
       if(resize != undefined){ 
           result[historyPointer] = resize;
           historyPointer++;
       }
       if(crop != undefined) {
	       result[historyPointer] = crop;
	       historyPointer++;
	   }	
	   if(other != undefined) {
	       for(var i =0;i<other.length;i++) {
	           result[historyPointer] = other[i];
	           historyPointer++;
	       }
	   }
	   return result;
	},
	
	enable: function() {
		this.isEnabled = true;
	},
	
	disable: function() {
		this.isEnabled = false;
	},
	
	clear: function() {
	   this.history = new Array();
       this.historyPointer = -1;
	   this.size = new Array();
	},
	
	removeLastOperation: function() {
		this.history.pop();
		this.size.pop();
		this.historyPointer--;		
	},
	
	isInHistory: function(operation,url) {
		if(operation == 'initialize' && this.historyPointer != -1) return true;
		for(var k=0;k<this.history.length;k++) {
			if(this.history[k].operation == operation && this.history[k].fileUrl == url) {
				return true;	
			}
		}
		return false;	
	},
	
	onImageLoad: function(event) {
		Event.stopObserving($('image'),'load',this.onImageLoad);
		this.image.style.width = this.size[this.historyPointer].width + 'px';
		this.image.style.height = this.size[this.historyPointer].height + 'px';
		$('imageContainer').style.width = this.size[this.historyPointer].width + 'px';
		$('imageContainer').style.height = this.size[this.historyPointer].height + 'px';
		ImageEditor.resize.imageContainerResize.originalWidth = this.size[this.historyPointer].width;
		ImageEditor.resize.imageContainerResize.originalHeight = this.size[this.historyPointer].height;
		ImageEditor.imageToResize.onImageLoad();
	},
	
	onlyResized: function() {
        for(i=this.historyPointer;i>=0;i--) {
            if(this.history[i].operation != 'resize') {
                return false;
            }
        }
        return true;
	},
	
	optimizeResize: function(history,size) {
	   var scallingXFactor = 1;var scallingYFactor = 1;
	   var initWidth = size[0].width;var initHeight = size[0].height;
	   for(var i=0;i<history.length && i < (this.historyPointer+1);i++) {
           switch(history[i].operation) {
               case 'resize':
                   if(i == 0) {
                       var previousWidth = 0;
                       var previousHeight = 0;
                   } else {
                       previousWidth = size[i-1].width;
                       previousHeight = size[i-1].height;
                   }                   
                   if(i != 0) {
                       scallingXFactor *= history[i].additionalInfo.width/previousWidth
                       scallingYFactor *= history[i].additionalInfo.height/previousHeight;
                   }                   
               break;  
               case 'rotate': 
                   var tempX = scallingXFactor;
                   scallingXFactor = scallingYFactor;
                   scallingYFactor = tempX;
                   
                   var tempW = initWidth;
                   initWidth = initHeight;
                   initHeight = tempW;
               break;             
           }    
       }
       if(scallingXFactor != 1 || scallingYFactor != 1) return {'operation': 'resize','additionalInfo': {'width': initWidth*scallingXFactor,'height': initHeight*scallingYFactor}};
	},
	
	optimizeCrop: function(history,size) {
		var imageWidth = size[0].width;var imageHeight = size[0].height;
		var topCrop = 0;var leftCrop = 0;
		var scallingXFactor = 1;var scallingYFactor = 1;
		var crops = new Array();var cropNumber = 0;var resizeNumber = 0;
		var lastResizeNumber = 0;
		for(var i=0;i<history.length && i < (this.historyPointer+1);i++) {
           switch(history[i].operation) {
               case 'crop':
                   crops[cropNumber] = {
                       'width': history[i].additionalInfo.width, 'height': history[i].additionalInfo.height,
                       'top': history[i].additionalInfo.top, 'left': history[i].additionalInfo.left,
                       'parent': {'width': size[i-1].width,'height': size[i-1].height}
                   };
                   imageWidth = crops[cropNumber].width;
                   imageHeight = crops[cropNumber].height;
                   cropNumber++;
                   scallingXFactor = 1;scallingYFactor = 1;
               break;
               case 'resize':
                   if(crops.length != 0) { 
	                   resizeNumber++;
	                   if(i == 0) {
	                       var previousWidth = 0;
	                       var previousHeight = 0;
	                   } else {
	                       previousWidth = size[i-1].width;
	                       previousHeight = size[i-1].height;
	                   }                   
	                   if(i != 0) {
	                       scallingXFactor = history[i].additionalInfo.width/previousWidth
	                       scallingYFactor = history[i].additionalInfo.height/previousHeight;
	                   }
	                   for(var k=0;k<crops.length;k++) {
	                       crops[k].top *= scallingYFactor;
	                       crops[k].left *= scallingXFactor;
	                       crops[k].parent.width *= scallingXFactor;
	                       crops[k].parent.height *= scallingYFactor;
	                       crops[k].width *= scallingXFactor;
	                       crops[k].height *= scallingYFactor; 
	                   }
	                   crops[cropNumber-1].width = history[i].additionalInfo.width;          
	                   crops[cropNumber-1].height = history[i].additionalInfo.height;
	               }
               break; 
               case 'rotate': 
                   if(crops.length != 0) {
                       for(var k = 0;k<crops.length;k++) {
                           var tempTop = crops[k].top;
                           crops[k].top = crops[k].parent.width - crops[k].width - crops[k].left;
                           crops[k].left = tempTop;
                           
                           tempWidth = crops[k].parent.width;
                           crops[k].parent.width = crops[k].parent.height;
                           crops[k].parent.height = tempWidth;
                           
                           var tempWidth = crops[k].width;
                           crops[k].width = crops[k].height;
                           crops[k].height = tempWidth;
                       }                        
                   }
               break;  
           }    
	    }
        for(var l=0;l<crops.length;l++) {
            topCrop += crops[l].top;
            leftCrop += crops[l].left;
        }        
        if(cropNumber == 0) return;
        if((topCrop + leftCrop + crops[cropNumber-1].width + crops[cropNumber-1].height) == 0) return;
        return {'operation': 'crop','additionalInfo': {'top': topCrop,'left': leftCrop,'width': crops[cropNumber-1].width,'height': crops[cropNumber-1].height}};
	},
	
	optimizeOtherEffects: function(history,without) {
        var result = Array();var effectsNumber = 0;var isInResult = false;
        for(var i=0;i<history.length;i++) {
            if(history[i].operation == 'resize' || history[i].operation == 'crop' || history[i].operation == 'rotate' || history[i].operation == without) continue;
            if(history[i].operation.indexOf('adjust') != -1) {
                isInResult = false;
                for(var k=0;k<result.length;k++) {
                    if(result[k].operation == history[i].operation) {
                        result[k] = history[i];
                        isInResult = true;
                    }
                }
                if(!isInResult) {
                    result[effectsNumber] = history[i];
                    effectsNumber++;
                }
            } else {
                result[effectsNumber] = history[i];
                effectsNumber++;
            }
        }
        return result;               
	},
	
	optimizeRotate: function(history) {
        var result = Array();var rotateNumber = 0;var rotate;
        for(var i=0;i<history.length;i++) {
            if(history[i].operation.indexOf('rotate') != -1) {
                rotateNumber++;
                rotate = history[i];
            }
        }
        rotateNumber = rotateNumber % 4;
        for(var i=0;i<rotateNumber;i++) {
            result[result.length] = rotate;
        }
        return result.length == 0 ? undefined : result;      
	},
	
	checkSpecialOperation: function(operationType,historyEntry) {
	    if(this.adjust[historyEntry.operation] != undefined) {
		    if(operationType == 'undo') {
		        if(this.adjust[historyEntry.operation].pointer > 0) this.adjust[historyEntry.operation].pointer--;               
		    } else {
		        this.adjust[historyEntry.operation].pointer++;
		    }
		    ImageEditor.effects.getEffect(historyEntry.operation).setValue(this.adjust[historyEntry.operation].values[this.adjust[historyEntry.operation].pointer]);
		}
	}
};