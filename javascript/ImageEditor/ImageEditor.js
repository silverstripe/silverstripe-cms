/**
 * @author Mateusz
 */
ImageEditor.Main = {
	initialize: function(imageFile) {
		imageFile += '1234';
		ImageEditor.crop = null;
		ImageEditor.imageHistory = new ImageEditor.ImageHistory.initialize();
		ImageEditor.environment = new ImageEditor.Environment.initialize(imageFile);		
		ImageEditor.imageTransformation = new ImageEditor.ImageTransformation.initialize();
		ImageEditor.resize = new ImageEditor.Resize.initialize($('imageContainer'));
		ImageEditor.effects = new ImageEditor.Effects.initialize();	
		ImageEditor.crop = new ImageEditor.Crop.initialize();
		ImageEditor.documentBody = new ImageEditor.DocumentBody.initialize();
		this.originalImageFile = imageFile;
		this.tottalyOriginalImageFile = imageFile;
		this.onSaveClick = ImageEditor.Main.onSaveClick.bind(this);
		this.onCloseClick = ImageEditor.Main.onCloseClick.bind(this);
		Event.observe($('SaveButton'),'click',this.onSaveClick);
		Event.observe($('ExitButton'),'click',this.onCloseClick);
		Element.hide($('CurrentAction'));
	}, 
	
	onSaveClick: function() {
		if(this.tottalyOriginalImageFile != $('image').src) {
			ImageEditor.imageTransformation.save(this.tottalyOriginalImageFile,$('image').src,this.onCloseClick);
		} else {
			this.onCloseClick();
		}
	},
	
	onCloseClick: function() {
		window.parent.imageEditorClosed();
		ImageEditor.imageTransformation.close(ImageEditor.Main.onCloseCallback.bind(this));
	},
	
	onCloseCallback: function() {
	    Element.hide(window.frameElement);
	}
	
}

