<?php

/**
 * Kurdish (Turkey) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('ku_TR', $lang) && is_array($lang['ku_TR'])) {
	$lang['ku_TR'] = array_merge($lang['en_US'], $lang['ku_TR']);
} else {
	$lang['ku_TR'] = $lang['en_US'];
}

$lang['ku_TR']['AssetAdmin']['MENUTITLE'] = 'Rûpelan & Xiyalan';
$lang['ku_TR']['CommentAdmin']['COMMENTS'] = 'Şîrovekirinan';
$lang['ku_TR']['CommentAdmin']['MENUTITLE'] = 'Şîrovekirinan';
$lang['ku_TR']['CommentAdmin']['MENUTITLE'] = 'Şîrovekirinan';
$lang['ku_TR']['CommentAdmin_left.ss']['COMMENTS'] = 'Şîrovekirinan';
$lang['ku_TR']['CommentAdmin_SiteTree.ss']['COMMENTS'] = 'Şîrovekirinan';
$lang['ku_TR']['LeftAndMain']['HELP'] = 'Arîkarî';
$lang['ku_TR']['PageCommentInterface.ss']['COMMENTS'] = 'Şîrovekirinan';
$lang['ku_TR']['SecurityAdmin']['MENUTITLE'] = 'Ewleyî';

?>