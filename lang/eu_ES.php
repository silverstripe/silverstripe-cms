<?php

/**
 * Basque (Spain) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('eu_ES', $lang) && is_array($lang['eu_ES'])) {
	$lang['eu_ES'] = array_merge($lang['en_US'], $lang['eu_ES']);
} else {
	$lang['eu_ES'] = $lang['en_US'];
}

$lang['eu_ES']['AssetAdmin']['MENUTITLE'] = 'Fitxategiak eta irudiak';
$lang['eu_ES']['CMSMain']['NEW'] = 'Berria';
$lang['eu_ES']['CMSMain_left.ss']['CREATE'] = 'Sortu';
$lang['eu_ES']['CMSMain_left.ss']['SEARCH'] = 'Bilaketa';
$lang['eu_ES']['LeftAndMain']['HELP'] = 'Laguntza';
$lang['eu_ES']['SecurityAdmin']['MENUTITLE'] = 'Segurtasuna';

?>