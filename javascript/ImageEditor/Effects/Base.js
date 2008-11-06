ImageEditor.Effects = {};
ImageEditor.Effects.Base = {
    initialize: function(effectName) {
        this.disabled = false;
        this.perform = ImageEditor.Effects.Base.perform.bind(this);
        this.setListener = ImageEditor.Effects.Base.setListener.bind(this);
        this.enable = ImageEditor.Effects.Base.enable.bind(this);        
        this.disable = ImageEditor.Effects.Base.disable.bind(this);
        this.callback = ImageEditor.Effects.Base.callback.bind(this);
        this.effectName = effectName;
        this.setListener();
    },
    
    perform: function() {
        if(!this.disabled) {
            ImageEditor.transformation.customRequest(this.effectName,this.callback,undefined,undefined,true);
        }
    },
    
    callback: function() {
        ImageEditor.history.addEffect($('image').src,this.effectName);
    },
    
    setListener: function(eventHandler) {
        var effectName = this.effectName.substring(0,1).toUpperCase() + this.effectName.substring(1,this.effectName.length);
        if($(effectName + 'Button')) {
            Event.observe(effectName + 'Button','click',this.perform);
        }
    },
    
    disable: function() {
        this.disabled = true;
    },
    
    enable: function() {
        this.disabled = false;
    }
}
ImageEditor.Effects = {};
ImageEditor.Effects.Base = {
    initialize: function(effectName) {
        this.disabled = false;
        this.perform = ImageEditor.Effects.Base.perform.bind(this);
        this.setListener = ImageEditor.Effects.Base.setListener.bind(this);
        this.enable = ImageEditor.Effects.Base.enable.bind(this);        
        this.disable = ImageEditor.Effects.Base.disable.bind(this);
        this.callback = ImageEditor.Effects.Base.callback.bind(this);
        this.effectName = effectName;
        this.setListener();
    },
    
    perform: function() {
        if(!this.disabled) {
            ImageEditor.transformation.customRequest(this.effectName,this.callback,undefined,undefined,true);
        }
    },
    
    callback: function() {
        ImageEditor.history.addEffect($('image').src,this.effectName);
    },
    
    setListener: function(eventHandler) {
        var effectName = this.effectName.substring(0,1).toUpperCase() + this.effectName.substring(1,this.effectName.length);
        if($(effectName + 'Button')) {
            Event.observe(effectName + 'Button','click',this.perform);
        }
    },
    
    disable: function() {
        this.disabled = true;
    },
    
    enable: function() {
        this.disabled = false;
    }
}