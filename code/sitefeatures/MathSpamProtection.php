<?php
/**
 * Tools for adding an optional Maths protection question to a form.
 * 
 * @package cms
 * @subpackage comments
 */

class MathSpamProtection {

	private static $mathProtection = false;
	
	/**
	 * Creates the question from random variables, which are also saved to the session.
	 * @return String
	 */
	static function getMathQuestion(){
		if(!Session::get("mathQuestionV1")&&!Session::get("mathQuestionV2")){
			$v1 = rand(1,9);
			$v2 = rand(1,9);
			Session::set("mathQuestionV1",$v1);
			Session::set("mathQuestionV2",$v2);
		}
		else{
			$v1 = Session::get("mathQuestionV1");
			$v2 = Session::get("mathQuestionV2");
		}
		return "What is ".MathSpamProtection::digitToWord($v1)." plus ".MathSpamProtection::digitToWord($v2)."?";
	}
	
	/**
	 * Checks the given answer if it matches the addition of the saved session variables. Users can answer using words or digits.
	 */
	static function correctAnswer($answer){
		$v1 = Session::get("mathQuestionV1");
		$v2 = Session::get("mathQuestionV2");
		
		Session::clear('mathQuestionV1');
		Session::clear('mathQuestionV2');
		
		if(MathSpamProtection::digitToWord($v1 + $v2) == $answer || ($v1 + $v2) == $answer){
			return true;
		}
		return false;
		
	}
	
	/**
	 * Helper method for converting digits to their equivelant english words
	 */
	static function digitToWord($num){
		$numbers = array("zero","one","two","three","four","five","six","seven","eight","nine",
										"ten","eleven","twelve","thirteen","fourteen","fifteen","sixteen","seventeen","eighteen");									
			if($num < 0){
				return "minus ".($numbers[-1*$num]);
			}
		//TODO: add checking or return null for bad value??
			return $numbers[$num];
	}
	
	
	static function isEnabled() {
		return self::$mathProtection;
	}
	
	static function setEnabled($math = true) {
		self::$mathProtection = $math;
	}

}
?>