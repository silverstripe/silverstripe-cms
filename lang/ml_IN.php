<?php

/**
 * Malayalam (India) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('ml_IN', $lang) && is_array($lang['ml_IN'])) {
	$lang['ml_IN'] = array_merge($lang['en_US'], $lang['ml_IN']);
} else {
	$lang['ml_IN'] = $lang['en_US'];
}

$lang['ml_IN']['AssetAdmin']['MENUTITLE'] = 'ഫയലുകളും ചിത്രങളും';
$lang['ml_IN']['AssetAdmin']['UPLOADEDX'] = '%s ';
$lang['ml_IN']['AssetTableField']['SIZE'] = 'വലിപ്പം';
$lang['ml_IN']['CMSMain']['NEW'] = 'പുതിയത്';
$lang['ml_IN']['CMSMain']['PAGENOTEXISTS'] = 'ഈ പേജ് നിലവിലില്ല';
$lang['ml_IN']['LeftAndMain']['HELP'] = 'സഹായം';
$lang['ml_IN']['PageCommentInterface.ss']['COMMENTS'] = 'അഭിപ്രായം';
$lang['ml_IN']['PageCommentInterface.ss']['NEXT'] = 'അടുത്തത്';
$lang['ml_IN']['PageCommentInterface.ss']['POSTCOM'] = 'നിങളുടെ അഭിപ്രായം ചേര്‍ക്കുക';
$lang['ml_IN']['PageCommentInterface_singlecomment.ss']['ISNTSPAM'] = 'ഈ അഭിപ്രായം സ്പാം അല്ല';
$lang['ml_IN']['PageCommentInterface_singlecomment.ss']['ISSPAM'] = 'ഈ അഭിപ്രായം സ്പാം ആണ്';

?>