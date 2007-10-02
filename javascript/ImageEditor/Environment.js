/**
 * @author Mateusz
 */
ImageEditor.Environment = {
	initialize: function (imageFile) {
		ImageEditor.imageBox = new ImageEditor.ImageBox.initialize();
		ImageEditor.imageToResize = new ImageEditor.ImageToResize.initialize(imageFile);		
	}		
}