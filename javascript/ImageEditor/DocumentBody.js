/**
 * @author Mateusz
 */
ImageEditor.DocumentBody = {
	initialize: function() {
		this.placeUI = ImageEditor.DocumentBody.placeUI.bind(this);
		this.placeUI();
		Event.observe(window.top,'resize',ImageEditor.DocumentBody.resizeIframe.bind(this));
	},
	
	resizeIframe: function(event) {
	    var windowWidth = Element.getDimensions(window.top.document.body).width;
        var windowHeight = Element.getDimensions(window.top.document.body).height;
        var iframe = window.top.document.getElementById('imageEditorIframe');
        iframe.style.width = windowWidth - 6 + 'px';
        iframe.style.height = windowHeight + 10 + 'px';
        this.placeUI();
	},
	
	placeUI: function() {
        var iframe = window.top.document.getElementById('imageEditorIframe');
        $('imageEditorContainer').style.height = Element.getDimensions(iframe).height - Element.getDimensions($('TopRuler')).height - Element.getDimensions($('MenuBar')).height - 32  + 'px';
        $('imageEditorContainer').style.width = Element.getDimensions(iframe).width - Element.getDimensions($('LeftRuler')).width - 14 + 'px';
        $('LeftRuler').style.height = $('imageEditorContainer').style.height; 
        $('TopLeft').style.width = Element.getDimensions($('MenuBar')).width -
                                   Element.getDimensions($('TopRight')).width + 'px';
        $('TopRight').style.left = Element.getDimensions($('TopLeft')).width + 'px';
                                   		
	},
	
	onImageEditorScroll: function() {
	   ImageEditor.imageBox.reCenterIndicator();
	}
}
