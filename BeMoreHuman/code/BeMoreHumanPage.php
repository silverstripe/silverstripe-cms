<?php
class BeMoreHumanPage extends Page {

	/**
	 * Static variable to store data alongside with the page instance.
	 * @var array
	 */
	public static $db = array(
		'PathToUploadedPictures'	=>	'Varchar', //Where to put the uploaded images
		'PathToWallPictures'	=>	'Varchar', //Where to put the Wall images
		'HeadText' 			=> "HTMLText", // Text above the the Form
		'WhatIsTheWall'		=> "HTMLText", // Text explaining what is the wall
		'FooterNotHidden'	=> "HTMLText", // Text in The footer which is not hidden
		'FooterHidden'		=> "HTMLText", // Text in The footer which is hidden at first
	);

	static $defaults = array(
		'PathToUploadedPictures' => 'assets/HowHumansWin/',
		'PathToWallPictures' => 'assets/TheWall/'
	);

	static $has_many = array(
		'HowHumansWinImages' => 'HowHumansWinImage',
		'WallImages'	=>'WallImage'

	);

	/**
	 * Overwrites SiteTree.getCMSFields to change the CMS form behaviour,
	*  i.e. by adding form fields for the additional attributes defined in
	 * {@link CataloguePage::$db}.
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldsToTab('Root.Content.BeMoreHuman',
			array(
				new TextField('PathToUploadedPictures','Path where to store the uploaded images from the form (e.g. assets/HowHumansWin/'),
				new TextField('PathToWallPictures','Path where to store the images from the wall (e.g. assets/TheWall/)'),
				new TextareaField('HeadText','Text before the form:',5,30),
				new TextareaField('WhatIsTheWall','What is the wall:',10,30),
				new TextareaField('FooterNotHidden','Footer - text always visible:',5,30),
				new TextareaField('FooterHidden','Footer - text extends the upper one:',10,30)
			));

		// return the modified fieldset.
		return $fields;
	}
}

/**
 * Controller Class for Main BeMoreHumanPage
 */
class BeMoreHumanPage_Controller extends Page_Controller {

	/**
	 * The Form
	 *
	 */
	function Form(){
		// Create fields
		$fields = new FieldSet(
			new TextField('Name'),
			new SimpleImageField(
				"PictureName",
				"Upload image below"
			  ),
			new TextareaField('Text')
		);

      // Create actions
      $actions = new FieldSet(
         new FormAction('TellHowHumansWinForm', 'Send')
      );

      return new Form($this, 'HowHumansWinForm', $fields, $actions);
	}// end Form

	function TellHowHumansWinForm(){

	}

	function getWallImageLink(){
		$image=DataObject::get_one('WallImage',NULL,false,'id DESC');
		if($image){
			$link=$image.Link();
		}
		else{
			$link=Director::baseURL().$this->PathToWallPictures . "/default.jpg";
		}
		return $link;
	}

	/**
	 * ThereAreTweets()
	 * Checks if there are tweets
	 * @returns bool true if there are otherwise false
	 *
	 */
	function ThereAreTweets(){
		return false;
	}

	function LatestTweets($howmany=4){
		return new DatObjectSet;
	}

} // end Controller


/**
 * BeMoreHumanPage Exception Class
 */
class BeMoreHumanPage_Exception extends Exception { }
