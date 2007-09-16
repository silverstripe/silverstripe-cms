/**
 * @author Mateusz
 */
var DocumentBody = {
	initialize: function() {
		var windowHeight = Element.getDimensions(window.top.document.body).height;
		Event.observe(window.top,'resize',DocumentBody.onWindowResize.bind(this));
		Event.observe($('imageEditorContainer'),'scroll',DocumentBody.onImageEditorScroll.bind(this));
		$('imageEditorContainer').style.height = windowHeight - 109 + 'px';
	},
	
	onWindowResize: function() {
        var windowWidth = Element.getDimensions(window.top.document.body).width;
        var windowHeight = Element.getDimensions(window.top.document.body).height;
        iframe = window.top.document.getElementById('imageEditorIframe');
        iframe.style.width = windowWidth - 30 + 'px';
        iframe.style.height = windowHeight + 10 + 'px';
        $('imageEditorContainer').style.height = windowHeight - 105 + 'px';		
	},
	
	onImageEditorScroll: function() {
	   imageBox.reCenterIndicator();
	}
}
