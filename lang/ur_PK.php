<?php

/**
 * Urdu (Pakistan) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('ur_PK', $lang) && is_array($lang['ur_PK'])) {
	$lang['ur_PK'] = array_merge($lang['en_US'], $lang['ur_PK']);
} else {
	$lang['ur_PK'] = $lang['en_US'];
}

$lang['ur_PK']['CMSMain']['NEW'] = 'نیا';
$lang['ur_PK']['CMSMain']['SAVE'] = 'محفوظ';
$lang['ur_PK']['SecurityAdmin']['MENUTITLE'] = 'حفاظت';

?>