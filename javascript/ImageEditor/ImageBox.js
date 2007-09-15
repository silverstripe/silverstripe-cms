/**
 * @author Mateusz
 */
var ImageBox = {
	
	initialize: function() {
		this.showIndicator = ImageBox.showIndicator.bind(this);
		this.hideIndicator = ImageBox.hideIndicator.bind(this);
		this.reCenterIndicator = ImageBox.reCenterIndicator.bind(this);
		this.centerIndicator = ImageBox.centerIndicator.bind(this);					
		this.checkOutOfDrawingArea = ImageBox.checkOutOfDrawingArea.bind(this);
		this.center = ImageBox.center.bind(this);
		this.imageContainer = $('imageContainer');
		Element.hide(this.imageContainer);
	},
		
	showIndicator: function() {
		this.centerIndicator();
		indicator.style.display = 'inline';	
	},
	
	hideIndicator: function() {
		Element.hide($('loadingIndicatorContainer'));
	},	
	
	centerIndicator: function() {
		indicator = $('loadingIndicatorContainer');
		indicatorImage = $('loadingIndicator');
		var top = this.imageContainer.getTop();
		var left = this.imageContainer.getLeft();
		var width = this.imageContainer.getWidth();
		var height = this.imageContainer.getHeight();
		var parentTop =  this.imageContainer.getParentTop();
		var parentLeft = this.imageContainer.getParentLeft();
		indicator.style.left = parentLeft + left + width/2 - indicatorImage.width/2 + 2 + "px"; 
		indicator.style.top = parentTop + top + height/2 - indicatorImage.height/2 + 2 + "px";		
	},
	
	reCenterIndicator: function() {
		if($('loadingIndicatorContainer').style.display == 'inline') {
			this.centerIndicator();
		}		
	},
	
	center: function() {
		$('imageContainer').style.left = this.imageContainer.getParentWidth()/2 - this.imageContainer.getWidth()/2 + 'px';
		$('imageContainer').style.top = this.imageContainer.getParentHeight()/2 - this.imageContainer.getHeight()/2 + 'px';
		Element.show(this.imageContainer);
	},
	
	checkOutOfDrawingArea: function(width,height) {
		var left =  this.imageContainer.getLeft();
		var top  =  this.imageContainer.getTop();
		var parentLeft = this.imageContainer.getParentLeft();
		var parentTop = this.imageContainer.getParentTop();
		var parentWidth = this.imageContainer.getParentWidth(); 
		var parentHeight = this.imageContainer.getParentHeight(); 
		if(left + width > parentWidth) {
			$('imageContainer').style.left = left - (left + width - parentWidth) - 3+ 'px';
		}
		if(top + height > parentHeight) {
			$('imageContainer').style.top = top - (top + height - parentHeight) - 3 + 'px';
		}	 
	}
	
	
};
