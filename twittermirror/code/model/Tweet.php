<?php

class Tweet extends DataObject {

	static $db = array(
        "created_at"        => "SSDatetime",
        "twitterid"         => "Varchar", //is is in json
        "text"              => "Varchar(150)",
        "iso_language_code" => "Varchar(5)",
        "profile_image_url" =>"Varchar",
        "from_user"         =>"Varchar",
        "from_user_id"      => "Varchar",
        "to_user"           => "Varchar",
        "to_user_id"        => "Varchar",
		"searchterm"		=> "Varchar"
	);

}

class Tweet_Controller extends Controller{

	private function TweetLink($ext='.json'){
		if ($this->twitterid)
			return "http://twitter.com/statuses/show/" . $this->twitterid . $ext;
		return NULL;
	}

	public function TweetLinkXML(){
		return TweetLink('.xml');
	}

	public function TweetLinkJSON(){
		return TweetLink('.json');
	}

	public function Link2FromTwitterUser(){
		if($this->from_user_id){
			return "http://twitter.com/" . $this->from_user_id;
		}
		return NULL;
	}

	public function Link2ToTwitterUser(){
		if($this->to_user_id){
			return "http://twitter.com/" . $this->to_user_id;
		}
		return NULL;
	}

	public function Link2Twitter($searchterm='#bemorehuman'){
		if(!$searchterm) return NULL;
		if($this->from_user_id){
			return "http://twitter.com/#search?q=" . urlencode($searchterm);
		}
		return NULL;
	}
}


?>
