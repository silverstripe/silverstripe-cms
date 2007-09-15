var ImageEditorActivator = {
	initialize: function() {
		this.onOpen = ImageEditorActivator.onOpen.bind(this);		
	},
	
	onOpen: function() {
		iframe = window.top.document.getElementById('imageEditorIframe');
		if(iframe != null) {
			iframe.parentNode.removeChild(iframe);
		}
		iframe = document.createElement('iframe');
		fileToEdit = $('ImageEditorActivator').firstChild.src;
		iframe.setAttribute("src","admin/ImageEditor?fileToEdit=" + fileToEdit);
		iframe.id = 'imageEditorIframe';
		iframe.style.width = "97%";
		iframe.style.height = "300%";
		iframe.style.zIndex = "1000";
		iframe.style.position = "absolute";
		iframe.style.top = "-2%";
		iframe.style.left = "1.5%";
		window.top.document.body.appendChild(iframe);
	}
		
}