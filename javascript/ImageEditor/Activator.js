var ImageEditorActivator = {
	initialize: function() {
		this.onOpen = ImageEditorActivator.onOpen.bind(this);		
	},
	
	onOpen: function() {
		var windowWidth = Element.getDimensions(window.top.document.body).width;
        var windowHeight = Element.getDimensions(window.top.document.body).height;
		iframe = window.top.document.getElementById('imageEditorIframe');
		if(iframe != null) {
			iframe.parentNode.removeChild(iframe);
		}
		iframe = window.top.document.createElement('iframe');
		fileToEdit = $('ImageEditorActivator').firstChild.src;
		iframe.setAttribute("src","admin/ImageEditor?fileToEdit=" + fileToEdit);
		iframe.id = 'imageEditorIframe';
		iframe.style.width = windowWidth - 30 + 'px';
		iframe.style.height = windowHeight + 10 + 'px';
		iframe.style.zIndex = "1000";
		iframe.style.position = "absolute";
		iframe.style.top = "-2%";
		iframe.style.left = "1.5%";
		window.top.document.body.appendChild(iframe);
		
		
	}
		
}