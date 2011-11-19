<?php

/**
 * Hindi (India) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('hi_IN', $lang) && is_array($lang['hi_IN'])) {
	$lang['hi_IN'] = array_merge($lang['en_US'], $lang['hi_IN']);
} else {
	$lang['hi_IN'] = $lang['en_US'];
}

$lang['hi_IN']['CMSMain']['CHOOSEREPORT'] = 'एक रिपोर्ट छुनो';
$lang['hi_IN']['CMSMain']['NEW'] = 'नया';
$lang['hi_IN']['CMSMain']['PAGENOTEXISTS'] = 'यह पृष नहीं है';
$lang['hi_IN']['CMSMain']['REMOVEDFD'] = 'हता दो ड्राफ्ट वेबसाइट से';
$lang['hi_IN']['CMSMain']['RESTORED'] = ' %s बछा सफलता से';
$lang['hi_IN']['CMSMain']['ROLLEDBACKPUB'] = 'पिछला पुबलिष  वषन | नया वषन नुमबेर है #%d';
$lang['hi_IN']['CMSMain']['ROLLEDBACKVERSION'] = 'वापस वषन #%d हो गया | नया वषन का नुमबेर #%d है';
$lang['hi_IN']['CMSMain']['SAVE'] = 'बचाओ';
$lang['hi_IN']['CMSMain']['VERSIONSNOPAGE'] = 'ये पृष #%d मिला नहि ';

?>