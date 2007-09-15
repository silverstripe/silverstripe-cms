/**
 * @author Mateusz
 */
 
Scriptaculous.require('cms/javascript/ImageEditor/Utils.js');
Scriptaculous.require('cms/javascript/ImageEditor/ImageHistory.js');
Scriptaculous.require('cms/javascript/ImageEditor/Image.js"');
Scriptaculous.require('cms/javascript/ImageEditor/ImageTransformation.js');
Scriptaculous.require('cms/javascript/ImageEditor/Resizeable.js');
Scriptaculous.require('cms/javascript/ImageEditor/Effects.js');
Scriptaculous.require('cms/javascript/ImageEditor/Environment.js');
Scriptaculous.require('cms/javascript/ImageEditor/Crop.js');
Scriptaculous.require('cms/javascript/ImageEditor/Resize.js');
Scriptaculous.require('cms/javascript/ImageEditor/ImageBox.js');
var ImageEditor = {
	initialize: function(imageFile) {
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
	}		
}
