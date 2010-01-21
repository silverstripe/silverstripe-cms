<?php

class TwitterMirror extends Object{


}

/**
 * @name TwitterMirror_Controller
 * @version 0.0.1
 *
 * This Ccontroller should be called from the commandline or better via the crond.
 * sake TwitterMirror_Controller
 *
 * Whern run it takes the max(id) from the Tweets and issues a
 * http://search.twitter.com/search.json?q={$Searchterm}&show_user=true&rpp=100&since_id={$maxid}
 * This gives the results of all Tweets tagged with #bemorehuman since the
 * latest stored tweet. The results are paged with 100 tweets per page.
 * If there are more than 100 results the response contain a "next_page" attribute.
 *
 * Before every request it is checked if there are at least 2 remaining api-hits.
 * http://twitter.com/account/rate_limit_status.json
 * The limits apply by IP basis or user basis if suthenticated,
 * so we have to check them before each run to
 * prevent our IP/account from being blocked
 *
 * You can set the credentials for the twitter account (user/pass) in _config.php
 * You can set the serchterm in _config.php
 *
 * @todo using the timeline, whern no searchterm is given.
 * http://twitter.com/statuses/user_timeline/{user}.json.
 *
 *
 */

class TwitterMirror_Controller extends Controller{

    protected static $twitteruser = 'twitteruser'; //'bemorehuman';
	protected static $twitterpass = 'twitterpass'; //'OkRC2YRKSXFo';
	protected static $Searchterm = 'TermToSearchFor'; //'%23bemorehuman';
	protected static $LogThis = 4; //ERRORs

	private static $LogLevels = array(
		0 => 'DEBUG',
		1 => 'INFO',
		2 => 'NOTICE',
		3 => 'WARNING',
		4 => 'ERROR',
		5 => 'CRITICAL',
		6 => 'Turn logging off',
		'DEBUG' => 0,
		'INFO' => 1,
		'NOTICE' => 2,
		'WARNING' => 3,
		'ERROR' => 4,
		'CRITICAL' => 5,
		'Turn logging off' => 6,
	);

	// Getter / Setter
	public function setLogThis($val){
		if(is_numeric($val) and ($val > 0) and ($val <= 5 )) self::$LogThis = $val;
		return;
	}

	public function getLogThis(){
		return self::$LogThis;
	}

	public function setSearchterm($val){
		$val=trim($val);
		if(!$val) return;
		self::$Searchterm = $val;
	}

	public function getSearchterm(){
		return self::$Searchterm;
	}

	public function setTwitteruser($val){
		$val=trim($val);
		if(!$val) return;
		self::$twitteruser =$val;
	}

	public function getTwitteruser(){
		return self::$twitteruser ;
	}

	public function setTwitterpass($val){
		$val=trim($val);
		if(!$val) return;
		self::$twitterpass =$val;
	}

	public function getTwitterpass(){
		return self::$twitterpass;
	}

    /**
     * twitter_hits_left()
     *
     * @return the numer of api-hits left or -1 on error
     *
     */
    private function twitter_hits_left(){
        $rate= new RestfulService("http://twitter.com/account/rate_limit_status.json", 0);
        $rate->basicAuth(self::$twitteruser,self::$twitterpass );
        $response = $rate->request(); //
        if($response->isError()){
			//@TODO Errorhandling
			$errormsg= $response->getStatusCode() .' '. $response->getStatusDescription();
			$this->logit("$errormsg",4);
           return -1;
        }
        $arryvalues = Convert::json2array($response->getbody());
		$this->logit("API requests left: " . $arryvalues['remaining_hits'], 1);
        return $arryvalues['remaining_hits'];
    }

	/**
	 * has_to_stop($myStartuptime)
	 * checks if there are at least 2 twitter api requests left
	 * and it must been less than 45 seconds the program has started
	 * (it is assumed that the program is run every minute,
	 * therfore 45 sec maximum runtime)
	 * @param $myStartuptime time in ticks when the program started
	 *
	 * @return bool true if one of the abort criterias are matched
	 * 				false if not
	 *
	 */
	private function has_to_stop($myStartuptime){
		// check runtime to avoid race conditions
		if((time() - $myStartuptime ) > 45){
			$this->logit("We have to stop because " . time() ." > $myStartuptime + 45", 2);
			return true;
		}
		//if we can get another page
		$hl=0;
		if(($hl=$this->twitter_hits_left()) <= 2){
			$this->logit("We have to stop because there are only $hl API-requests left",2);
			return true;
		}
		$this->logit("$hl API-requests left",1);
		return false;
	}

	/**
	 * logit($text='--MARK--',$loglevel=1)
	 * echos $text to STDOUT if $loglevel is greater or equal $LogThis, which
	 * can be set in _config.php via
	 * TwitterMirror_Controller::setLogThis(1);
	 * Valid values are 0 .. 6
	 * Where 6 turns the logging off
	 * 		0 => 'DEBUG',
	 * 		1 => 'INFO',
	 * 		2 => 'NOTICE',
	 * 		3 => 'WARNING',
	 * 		4 => 'ERROR',
	 * 		5 => 'CRITICAL',
	 * 		6 => 'Turn logging off'
	 *
	 * @param $text Text to log
	 * @param $loglevel Level of importance of the log-entry
	 *
	 *
	 */

	private function logit($text='--MARK--',$loglevel=1){ //all without logleve are handeled as Info
		if($loglevel < $this->LogThis) return;
		echo time() . " [". $this->getSearchterm() ."] " .$this->LogLevels[$loglevel]. ": " . $text . "\n";
	}


	/**
	 * index()
	 * get's the newest feeds from twitter marked with {Searchterm}
	 * and stores them in the database;
	 *
	 */

    public function index() {
		$myStartuptime=time(); // keep the start time, because in 59s we run again ;0)
		$this->logit("Starting at: $myStartuptime",0);
		$searchterm=$this->getSearchterm();
		if($this->has_to_stop($myStartuptime)) return ; // we need at least 2 api hits left and a bit of time
		$max_id=0;
		$filter="searchterm = '". $searchterm ."'";
		$this->logit("searchterm=[$searchterm]\n filter is ==>$filter<==",0);
		$tweet=DataObject::get_one('Tweet',$filter, false,'twitterid DESC');
		if($tweet){
			$max_id = $tweet->twitterid;
		}
		$this->logit("max_id is $max_id",0);
		$params = array(
			'q' => $searchterm,
			'show_user' => 'true',
			'rpp' => 100,
		);

		if($max_id) $params['since_id']=$max_id; // search only for newer tweets
		//Check for new tweets with #bemorehuman (searchterm)
		//http://search.twitter.com/search.json?q=%23bemorehuman&show_user=true&rpp=100
		$rest= new RestfulService("http://search.twitter.com/search.json", 0);
        $rest->basicAuth(self::$twitteruser,self::$twitterpass );
		$DoneWithIt=false;
		while(!$DoneWithIt){
			$rest->setQueryString($params);
			$response = $rest->request(); //
			if($response->isError()){
				//@TODO Errorhandling
				$errormsg= $response->getStatusCode() .' '. $response->getStatusDescription();
				$this->logit($errormsg,4);
				return ;
			}
			$obj=Convert::json2obj($response->getbody());

			// checking the results
			$results=$obj->results;
			if(!is_array($results)){
				// This should always be an array even if empty, so we assume the complete
				// object to be corrupt.
				//@TODO Throw Fatal Error or something simular
				$this->logit("results should be an array",5);
				die;
			}

			if(($i = count($results)) <= 0){
				$DoneWithIt=true;
			}
			else{
				$this->logit("processing $i new entires",0);
				//Process the resuls
				echo "\n\n";
				foreach( $results as $result){
					$tweet = new Tweet;
					$d=DateTime::createFromFormat("D, j M Y H:i:s O", $result->created_at); //"Thu, 14 Jan 2010 02:46:45 +0000"
					$tweet->created_at = $d->format("Y-m-d H:i:s");
					$tweet->twitterid = $result->id;
					$tweet->text = $result->text;
					if(isset($result->iso_language_code)) $tweet->iso_language_code = $result->iso_language_code;
					else $tweet->iso_language_code = "xx";
					if(isset($result->profile_image_url)) $tweet->profile_image_url = $result->profile_image_url;
					$tweet->from_user_id = $result->from_user_id;
					$tweet->from_user = $result->from_user_id;
					if(isset($result->to_user_id)) $tweet->to_user_id =  $result->to_user_id;
					if(isset($result->to_user)) $tweet->to_user =  $result->to_user;
					$tweet->searchterm = $searchterm;
					$tweet->write();
					$this->logit("Adding tweet " . $tweet->twitterid. " from " .$tweet->from_user . " (". $tweet->text .")",0);
					//return;
				}
				//all results from this page should be in the DB now
				// let's see if there is more to come
				if(isset($obj->next_page)){
					$t1=strpos($obj->next_page,'page=');
					if($t1){
						$t=substr($obj->next_page,$t1 + 5);  //page=
				        $t2=strpos($t,'&');
				        if($t2){
				            $t=substr($t,0,$t2);
				        }
						$params['page']=$t + 0; //Set the page parameter
				    }
				    else{
				        // "no page= found\n";
						$this->logit("No next page info in next_page",2);
						$DoneWithIt=true;
				    }
				}
				else{
					$this->logit("No next page",0);
					$DoneWithIt=true;
				}
				if($this->has_to_stop($myStartuptime)) return;
			}
		}
		return ;
    }
}


?>
