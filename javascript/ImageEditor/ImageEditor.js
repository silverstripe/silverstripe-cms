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
		this.onSaveClick = ImageEditor.onSaveClick.bind(this);
		this.onCloseClick = ImageEditor.onCloseClick.bind(this);
		Event.observe($('saveButton'),'click',this.onSaveClick);
		Event.observe($('closeButton'),'click',this.onCloseClick);				
	}, 
	onSaveClick: function() {
		if(this.tottalyOriginalImageFile != $('image').src) {
			imageTransformation.save(this.tottalyOriginalImageFile,$('image').src,this.onCloseClick);
		} else {
			this.onCloseClick();
		}
	},
	
	onCloseClick: function() {
		window.parent.frames[0].location.reload(1);
		window.parent.frames[1].location.reload(1);
		imageTransformation.close(ImageEditor.onCloseCallback.bind(this));
	},
	
	onCloseCallback: function() {
	    Element.hide(window.frameElement);
	}
	
}

