ImageEditor.Adjust = {
    initialize: function() {
        this.perform = ImageEditor.Adjust.perform.bind(this); 
        this.setListener = ImageEditor.Adjust.setListener.bind(this);
        this.setListener();
    },
    
    setListener: function() {
        Element.toggle($('AdjustMenu'));   
        Event.observe('AdjustButton','click',this.perform);
    },
    
    perform: function() {
        Element.toggle($('AdjustMenu'));            
    }
}  