<?php

/**
 * English (Spain) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('en_ES', $lang) && is_array($lang['en_ES'])) {
	$lang['en_ES'] = array_merge($lang['en_US'], $lang['en_ES']);
} else {
	$lang['en_ES'] = $lang['en_US'];
}

$lang['en_ES']['AssetAdmin']['MENUTITLE'] = 'Files & Images';
$lang['en_ES']['CMSMain']['MENUTITLE'] = 'Site Content';
$lang['en_ES']['CMSMain_left.ss']['CREATE'] = 'Create';
$lang['en_ES']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Site Content and Estructure';
$lang['en_ES']['LeftAndMain']['HELP'] = 'Help';
$lang['en_ES']['SecurityAdmin']['MENUTITLE'] = 'Security';

?>