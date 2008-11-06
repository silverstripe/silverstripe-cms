/**
 * @author Mateusz
 */
ImageEditor.Effects.Main = {
	initialize: function() {
		this.enable = ImageEditor.Effects.Main.enable.bind(this);
		this.disable = ImageEditor.Effects.Main.disable.bind(this);
		this.effects = Array();
		this.effects['rotate'] = new ImageEditor.Effects.Base.initialize('rotate');
		this.effects['greyscale'] = new ImageEditor.Effects.Base.initialize('greyscale');
		this.effects['sepia'] = new ImageEditor.Effects.Base.initialize('sepia');
		this.effects['blur'] = new ImageEditor.Effects.Base.initialize('blur');
		this.effects['adjust-contrast'] = new ImageEditor.Effects.AdjustBase.initialize('adjust-contrast',$R(-100, 100),0.1,62);
		this.effects['adjust-brightness'] = new ImageEditor.Effects.AdjustBase.initialize('adjust-brightness',$R(-255, 255),0.1,160);
		this.effects['adjust-gamma'] = new ImageEditor.Effects.AdjustBase.initialize('adjust-gamma',$R(0, 5),1.2,4);
		this.getEffect = ImageEditor.Effects.Main.getEffect.bind(this);
	},
	
	enable: function() {
 	    for (var name in this.effects) { 
             if(this.effects.hasOwnProperty(name)) this.effects[name].enable();            
         }
  	},
  	
 	disable: function() {
 	    for (var name in this.effects) { 
             if(this.effects.hasOwnProperty(name)) this.effects[name].disable();
         }
  	},
  	
 	getEffect: function(name) {
 	    return this.effects[name];
  	}
		
}