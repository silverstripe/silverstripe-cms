<?php

/**
 * Welsh (United Kingdom) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('cy_GB', $lang) && is_array($lang['cy_GB'])) {
	$lang['cy_GB'] = array_merge($lang['en_US'], $lang['cy_GB']);
} else {
	$lang['cy_GB'] = $lang['en_US'];
}

$lang['cy_GB']['LeftAndMain']['HELP'] = 'Help';
$lang['cy_GB']['SecurityAdmin_right.ss']['WELCOME1'] = 'Croeso i';

?>