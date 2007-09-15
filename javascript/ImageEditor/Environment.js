/**
 * @author Mateusz
 */
var Environment = {
	initialize: function (imageFile) {
		imageBox = new ImageBox.initialize();
		imageToResize = new ImageToResize.initialize(imageFile);		
	}		
}