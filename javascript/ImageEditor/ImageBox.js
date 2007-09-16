/**
 * @author Mateusz
 */
var ImageBox = {
	
	initialize: function() {
		this.indicatorWidth = 32;
		this.indicatorHeight = 32;
		this.showIndicator = ImageBox.showIndicator.bind(this);
		this.hideIndicator = ImageBox.hideIndicator.bind(this);
		this.reCenterIndicator = ImageBox.reCenterIndicator.bind(this);
		this.centerIndicator = ImageBox.centerIndicator.bind(this);					
		this.center = ImageBox.center.bind(this);
		this.imageContainer = Positioning.addBehaviour($('imageContainer'));
		Element.hide(this.imageContainer);
		this.indicator = Positioning.addBehaviour($('loadingIndicatorContainer'));
        this.indicatorImage = Positioning.addBehaviour($('loadingIndicator'));
		Positioning.addBehaviour($('mainContainer'));
	},
		
	showIndicator: function(container) {
		Element.show(this.indicator,this.indicatorImage);
		if(container == null) container = this.imageContainer;
		this.centerIndicator(container);
	},
	
	hideIndicator: function() {
		Element.hide(this.indicator,this.indicatorImage);
	},	
	
	centerIndicator: function(container) {
		var top = container.getTop();
		var left = container.getLeft();
		var width = container.getWidth();
		var height = container.getHeight();
		var parentTop =  container.getParentTop();
		var parentLeft = container.getParentLeft();
		this.indicator.style.left = width/2 - this.indicatorWidth/2 + "px"; 
		this.indicator.style.top = height/2 - this.indicatorHeight/2 + "px";
	},
	
	reCenterIndicator: function() {
		if(Element.visible(this.indicator)) {
			this.centerIndicator(this.imageContainer);
		}		
	},
	
	center: function() {
		this.imageContainer.style.left = this.imageContainer.getParentWidth()/2 - this.imageContainer.getWidth()/2 + 'px';
		this.imageContainer.style.top = this.imageContainer.getParentHeight()/2 - this.imageContainer.getHeight()/2 + 'px';
	}
};
