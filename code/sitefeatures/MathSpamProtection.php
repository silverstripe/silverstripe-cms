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
		
		return sprintf(
			_t('MathSpamProtection.WHATIS',"What is %s plus %s?"), 
			MathSpamProtection::digitToWord($v1), 
			MathSpamProtection::digitToWord($v2)
		);
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
		$numbers = array(_t('MathSpamProtection.ZERO', 'zero'),
			_t('MathSpamProtection.ONE', 'one'),
			_t('MathSpamProtection.TWO', 'two'),
			_t('MathSpamProtection.THREE', 'three'),
			_t('MathSpamProtection.FOUR', 'four'),
			_t('MathSpamProtection.FIVE', 'five'),
			_t('MathSpamProtection.SIX', 'six'),
			_t('MathSpamProtection.SEVEN', 'seven'),
			_t('MathSpamProtection.EIGHT', 'eight'),
			_t('MathSpamProtection.NINE', 'nine'),
			_t('MathSpamProtection.TEN', 'ten'),
			_t('MathSpamProtection.ELEVEN', 'eleven'),
			_t('MathSpamProtection.TWELVE', 'twelve'),
			_t('MathSpamProtection.THIRTEEN', 'thirteen'),
			_t('MathSpamProtection.FOURTEEN', 'fourteen'),
			_t('MathSpamProtection.FIFTEEN', 'fifteen'),
			_t('MathSpamProtection.SIXTEEN', 'sixteen'),
			_t('MathSpamProtection.SEVENTEEN', 'seventeen'),
			_t('MathSpamProtection.EIGHTEEN', 'eighteen'));			
									
			if($num < 0) return "minus ".($numbers[-1*$num]);
						
		return $numbers[$num];
	}
	
	
	static function isEnabled() {
		return self::$mathProtection;
	}
	
	static function setEnabled($math = true) {
		self::$mathProtection = $math;
	}

}