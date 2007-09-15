/**
 * @author Mateusz
 */
 
var ImageEditor = {
	initialize: function(imageFile) {
		imageFile += '1234';
		crop = null;
		imageHistory = new ImageHistory.initialize();
		environment = new Environment.initialize(imageFile);		
		imageTransformation = new ImageTransformation.initialize();
		resize = new Resize.initialize($('imageContainer'));
		effects = new Effects.initialize();	
		crop = new Crop.initialize();
		this.originalImageFile = imageFile;
		this.tottalyOriginalImageFile = imageFile;
		this.onSave = ImageEditor.onSave.bind(this);
		this.onClose = ImageEditor.onClose.bind(this);
		Event.observe($('saveButton'),'click',this.onSave);
		Event.observe($('closeButton'),'click',this.onClose);				
		imageToResize.onImageLoad();
		resize.imageContainerResize.placeClickBox();
	}, 
	onSave: function() {
		if(this.tottalyOriginalImageFile != $('image').src) {
			imageTransformation.save(this.tottalyOriginalImageFile,$('image').src);
		} else {
			this.onClose();
		}
	},
	
	onClose: function() {
		window.parent.frames[1].location.reload(1);
		Element.hide(window.frameElement);
		imageTransformation.close();
	}		
}

