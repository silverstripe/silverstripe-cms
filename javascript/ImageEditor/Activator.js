ImageEditor = {};

ImageEditor.Activator = {
	initialize: function() {
		this.onOpen = ImageEditor.Activator.onOpen.bind(this);		
	},
	
	onOpen: function() {
		var windowWidth = Element.getDimensions(window.top.document.body).width;
        var windowHeight = Element.getDimensions(window.top.document.body).height;
		var iframe = window.top.document.getElementById('imageEditorIframe');
		if(iframe != null) {
			iframe.parentNode.removeChild(iframe);
		}
		iframe = window.top.document.createElement('iframe');
		var fileToEdit = $('ImageEditorActivator').firstChild.src;
		iframe.setAttribute("src","admin/ImageEditor?fileToEdit=" + fileToEdit);
		iframe.id = 'imageEditorIframe';
		iframe.style.width = windowWidth - 6 + 'px';
		iframe.style.height = windowHeight + 10 + 'px';
		iframe.style.zIndex = "1000";
		iframe.style.position = "absolute";
		iframe.style.top = "8px";
		iframe.style.left = "8px";
		window.top.document.body.appendChild(iframe);
		var divLeft = window.top.document.createElement('div');
		var divRight = window.top.document.createElement('div');
        divLeft.style.width = "8px";
        divLeft.style.height = "300%";
        divLeft.style.zIndex = "1000";
        divLeft.style.top = "0";
        divLeft.style.position = "absolute";
        divRight.style.width = "10px";
        divRight.style.height = "300%";
        divRight.style.zIndex = "1000";
        divRight.style.top = "0";
        divRight.style.position = "absolute";
        divRight.style.left = Element.getDimensions(divLeft).width + Element.getDimensions(iframe).width - 4 + 'px';
		window.top.document.body.appendChild(divLeft);
		window.top.document.body.appendChild(divRight);
	}
		
}