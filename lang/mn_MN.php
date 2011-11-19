<?php

/**
 * Mongolian (Mongolia) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('mn_MN', $lang) && is_array($lang['mn_MN'])) {
	$lang['mn_MN'] = array_merge($lang['en_US'], $lang['mn_MN']);
} else {
	$lang['mn_MN'] = $lang['en_US'];
}

$lang['mn_MN']['AssetAdmin']['MENUTITLE'] = 'Файлууд & Зургууд';
$lang['mn_MN']['CMSMain']['MENUTITLE'] = 'Сайтын агуулга';
$lang['mn_MN']['CMSMain']['NEW'] = 'Шинэ';
$lang['mn_MN']['CMSMain']['RESTORED'] = '\'%s\' амжилттай сэргээгдлээ.';
$lang['mn_MN']['CMSMain_left.ss']['CREATE'] = 'Үүсгэх';
$lang['mn_MN']['CMSMain_left.ss']['SEARCH'] = 'Хайлт';
$lang['mn_MN']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Сайтын агуулга ба Бүтэц';
$lang['mn_MN']['CommentAdmin']['MENUTITLE'] = 'Сэтгэгдэлүүд';
$lang['mn_MN']['LeftAndMain']['HELP'] = 'Тусламж';
$lang['mn_MN']['LeftAndMain']['SITECONTENTLEFT'] = 'Сайтын агуулга';
$lang['mn_MN']['LeftAndMain.ss']['LOADING'] = 'Ачааллаж байна...';
$lang['mn_MN']['MemberList.ss']['FILTER'] = 'Шүүлтүүр';
$lang['mn_MN']['MemberTableField.ss']['ADDNEW'] = 'Шинээр нэмэх';
$lang['mn_MN']['ReportAdmin_right.ss']['WELCOME1'] = 'Тавтай морилно уу';
$lang['mn_MN']['SecurityAdmin']['MENUTITLE'] = 'Хамгаалалт';
$lang['mn_MN']['SecurityAdmin_right.ss']['WELCOME2'] = 'Хамгаалалтын удирдах хэсэг. Зүүн талаас бүлгээ сонгоно уу.';

?>