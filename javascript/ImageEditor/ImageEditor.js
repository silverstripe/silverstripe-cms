/**
 * @author Mateusz
 */
ImageEditor.Main = {
	initialize: function(imageFile) {
		imageFile += '1234';
		ImageEditor.crop = null;
		ImageEditor.history = new ImageEditor.History.initialize();
		ImageEditor.environment = new ImageEditor.Environment.initialize(imageFile);		
		ImageEditor.transformation = new ImageEditor.Transformation.initialize();
		ImageEditor.resize = new ImageEditor.Resize.initialize($('imageContainer'));
		ImageEditor.effects = new ImageEditor.Effects.Main.initialize();	
		ImageEditor.crop = new ImageEditor.Crop.initialize();
		ImageEditor.documentBody = new ImageEditor.DocumentBody.initialize();
		ImageEditor.adjust = new ImageEditor.Adjust.initialize();
		this.originalImageFile = imageFile;
		this.tottalyOriginalImageFile = imageFile;
		this.onSaveClick = ImageEditor.Main.onSaveClick.bind(this);
		this.onCloseClick = ImageEditor.Main.onCloseClick.bind(this);
		this.enableFunctionality = ImageEditor.Main.enableFunctionality.bind(this);
		this.disableFunctionality = ImageEditor.Main.disableFunctionality.bind(this);
		Event.observe($('SaveButton'),'click',this.onSaveClick);
		Event.observe($('ExitButton'),'click',this.onCloseClick);
		Element.hide($('CurrentAction'));
	}, 
	
	onSaveClick: function() {
		if(this.tottalyOriginalImageFile != $('image').src) {
			ImageEditor.transformation.save(this.tottalyOriginalImageFile,$('image').src,this.onCloseClick);
		} else {
			this.onCloseClick();
		}
	},
	
	onCloseClick: function() {
		window.parent.imageEditorClosed();
		ImageEditor.transformation.close(ImageEditor.Main.onCloseCallback.bind(this));
	},
	
	onCloseCallback: function() {
	    Element.hide(window.frameElement);
	},
	
	enableFunctionality: function() {
       ImageEditor.effects.enable();
       ImageEditor.crop.enable();
       ImageEditor.resize.enable();
       ImageEditor.history.enable();
    },
    
    disableFunctionality: function() {
       ImageEditor.effects.disable();
       ImageEditor.crop.disable();
       ImageEditor.resize.disable();
       ImageEditor.history.disable();
    }
}