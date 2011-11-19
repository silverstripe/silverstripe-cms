<?php

/**
 * Kannada (India) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('kn_IN', $lang) && is_array($lang['kn_IN'])) {
	$lang['kn_IN'] = array_merge($lang['en_US'], $lang['kn_IN']);
} else {
	$lang['kn_IN'] = $lang['en_US'];
}

$lang['kn_IN']['AssetAdmin']['MENUTITLE'] = 'ಫೈಲ್ಗಳು ಮತ್ತು ಚಿತ್ರಗಳು';
$lang['kn_IN']['CMSMain']['MENUTITLE'] = 'ತಾಣದ ವಿಷಯಗಳು';
$lang['kn_IN']['CMSMain_left.ss']['BATCHACTIONS'] = 'ಒಟ್ಟಾಗಿ ಮಾಡು';
$lang['kn_IN']['CMSMain_left.ss']['CREATE'] = 'ರಚಿಸಿ';
$lang['kn_IN']['CMSMain_left.ss']['SEARCH'] = 'ಹುಡುಕು';
$lang['kn_IN']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'ತಾಣದ ವಿಷಯಗಳು ಮತ್ತು ರಚನೆ';
$lang['kn_IN']['CommentAdmin']['MENUTITLE'] = 'ಟಿಪ್ಪಣಿಗಳು';
$lang['kn_IN']['LeftAndMain']['HELP'] = 'ಸಹಾಯ';
$lang['kn_IN']['LeftAndMain.ss']['LOADING'] = 'ಲೋಡಿಂಗ್ ...';
$lang['kn_IN']['SecurityAdmin']['MENUTITLE'] = 'ಭದ್ರತೆ';

?>