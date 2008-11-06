ImageEditor.Effects.AdjustBase = {
    initialize: function(name,minMax,firstValue,maxValue) {
        this.name = name;
        this.minMax = minMax;
        this.firstValue = firstValue;
        this.maxValue = maxValue;
        this.setListener = ImageEditor.Effects.AdjustBase.setListener.bind(this);
        this.callback = ImageEditor.Effects.AdjustBase.callback.bind(this);
        this.setValue = ImageEditor.Effects.AdjustBase.setValue.bind(this);
        this.getDefaultValue = ImageEditor.Effects.AdjustBase.getDefaultValue.bind(this);
        this.setListener();
        this.lastValue = this.firstValue;
        this.stopListenining = false;
    },
    
    setListener: function() {
        var upperCaseName = this.name.substring(7,8).toUpperCase() + this.name.substring(8,this.name.length);
        this.slider = new Control.Slider('AdjustMenu' + upperCaseName + 'SliderTrackHandler','AdjustMenu' + upperCaseName + 'SliderTrack', {
            range: this.minMax,
            sliderValue: this.firstValue,
            onChange: ImageEditor.Effects.AdjustBase.onChange.bind(this),
            onSlide: ImageEditor.Effects.AdjustBase.onSlide.bind(this)
        });
    },
    
    onSlide: function(v) {
        if(this.disabled || this.stopListenining) return;
        if(v > this.maxValue) this.setValue(this.maxValue);
    },
    
    onChange: function(v) {
        if(this.disabled || this.stopListenining) return;
        this.lastValue = v;
        file = $('image').src;
        if(ImageEditor.history.hasOperation(this.name)) {
           var history = ImageEditor.history.getOptimizedHistory(this.name);
           if(history[1] != undefined) {
               file = ImageEditor.transformation.applyHistory(history);
           } else {
               file = history[0].fileUrl;
           }
        }
        ImageEditor.transformation.customRequest(this.name,this.callback,file,this.lastValue,true);
    },
    
    callback: function() {
        ImageEditor.history.addAdjust(this.name,this.lastValue,$('image').src);            
    },
    
    setValue: function(value) {
        this.stopListenining = true;
        this.slider.setValue(value);
        this.stopListenining = false;
    }, 
    
    getDefaultValue: function() {
        return this.firstValue;
    }        
}
ImageEditor.Effects.AdjustBase.initialize.prototype = new ImageEditor.Effects.Base.initialize("adjustbase");
ImageEditor.Effects.AdjustBase = {
    initialize: function(name,minMax,firstValue,maxValue) {
        this.name = name;
        this.minMax = minMax;
        this.firstValue = firstValue;
        this.maxValue = maxValue;
        this.setListener = ImageEditor.Effects.AdjustBase.setListener.bind(this);
        this.callback = ImageEditor.Effects.AdjustBase.callback.bind(this);
        this.setValue = ImageEditor.Effects.AdjustBase.setValue.bind(this);
        this.getDefaultValue = ImageEditor.Effects.AdjustBase.getDefaultValue.bind(this);
        this.setListener();
        this.lastValue = this.firstValue;
        this.stopListenining = false;
    },
    
    setListener: function() {
        var upperCaseName = this.name.substring(7,8).toUpperCase() + this.name.substring(8,this.name.length);
        this.slider = new Control.Slider('AdjustMenu' + upperCaseName + 'SliderTrackHandler','AdjustMenu' + upperCaseName + 'SliderTrack', {
            range: this.minMax,
            sliderValue: this.firstValue,
            onChange: ImageEditor.Effects.AdjustBase.onChange.bind(this),
            onSlide: ImageEditor.Effects.AdjustBase.onSlide.bind(this)
        });
    },
    
    onSlide: function(v) {
        if(this.disabled || this.stopListenining) return;
        if(v > this.maxValue) this.setValue(this.maxValue);
    },
    
    onChange: function(v) {
        if(this.disabled || this.stopListenining) return;
        this.lastValue = v;
        file = $('image').src;
        if(ImageEditor.history.hasOperation(this.name)) {
           var history = ImageEditor.history.getOptimizedHistory(this.name);
           if(history[1] != undefined) {
               file = ImageEditor.transformation.applyHistory(history);
           } else {
               file = history[0].fileUrl;
           }
        }
        ImageEditor.transformation.customRequest(this.name,this.callback,file,this.lastValue,true);
    },
    
    callback: function() {
        ImageEditor.history.addAdjust(this.name,this.lastValue,$('image').src);            
    },
    
    setValue: function(value) {
        this.stopListenining = true;
        this.slider.setValue(value);
        this.stopListenining = false;
    }, 
    
    getDefaultValue: function() {
        return this.firstValue;
    }        
}
ImageEditor.Effects.AdjustBase.initialize.prototype = new ImageEditor.Effects.Base.initialize("adjustbase");