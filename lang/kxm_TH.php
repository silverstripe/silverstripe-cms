<?php

/**
 * Khmer, Northern (Thailand) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('kxm_TH', $lang) && is_array($lang['kxm_TH'])) {
	$lang['kxm_TH'] = array_merge($lang['en_US'], $lang['kxm_TH']);
} else {
	$lang['kxm_TH'] = $lang['en_US'];
}

$lang['kxm_TH']['AssetAdmin']['MENUTITLE'] = 'រូបភាព និងឯកសារ';
$lang['kxm_TH']['AssetAdmin']['MENUTITLE'] = 'រូបភាព និង ឯកសារ';
$lang['kxm_TH']['AssetAdmin_left.ss']['ENABLEDRAGGING'] = 'ប្រើប្រាស់ការទាញទៅមកបាន';
$lang['kxm_TH']['CMSMain']['MENUTITLE'] = 'អត្ថបទ';
$lang['kxm_TH']['CMSMain']['MENUTITLE'] = 'ទំព័រ';
$lang['kxm_TH']['CMSMain']['NEW'] = 'ថ្មី';
$lang['kxm_TH']['CMSMain']['RESTORED'] = 'បានដាក់ \'%s\'  វិញរួចរាល់';
$lang['kxm_TH']['CMSMain']['VIEWING'] = 'អ្នកកំពុងមើលសេរី #%s, បានបងើ្កត%s ដោយ %s ';
$lang['kxm_TH']['CMSMain_left.ss']['CREATE'] = 'បង្កើត';
$lang['kxm_TH']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'ប្រើប្រាស់ការទាញទៅមកបាន';
$lang['kxm_TH']['CMSMain_left.ss']['SEARCH'] = 'ស្វែងរក';
$lang['kxm_TH']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'សម្ព័ន្ធទំព័រ';
$lang['kxm_TH']['CommentAdmin']['MENUTITLE'] = 'យោបល់';
$lang['kxm_TH']['CommentAdmin']['MENUTITLE'] = 'យោបល់';
$lang['kxm_TH']['LeftAndMain']['HELP'] = 'ជំនួយការ';
$lang['kxm_TH']['LeftAndMain']['SITECONTENTLEFT'] = 'អត្ថបទគេហទំព័រ';
$lang['kxm_TH']['LeftAndMain.ss']['LOADING'] = 'កំពុងដំណើរការ...';
$lang['kxm_TH']['LeftAndMain.ss']['REQUIREJS'] = 'CMS ត្រូវការប្រើប្រាស់ Javascript';
$lang['kxm_TH']['MemberList.ss']['FILTER'] = 'ជំរើស';
$lang['kxm_TH']['MemberTableField.ss']['ADDNEW'] = 'បង្កើតថ្មី';
$lang['kxm_TH']['ReportAdmin']['MENUTITLE'] = 'របាយការណ៍';
$lang['kxm_TH']['ReportAdmin_right.ss']['WELCOME1'] = 'សូមស្វាគមន៍ មកកាន់';
$lang['kxm_TH']['ReportAdmin_right.ss']['WELCOME2'] = 'ផ្នែករបាយការណ័។  សូមជ្រើរើសប្រអប់នៅខាងឆ្វេង។';
$lang['kxm_TH']['SecurityAdmin']['MENUTITLE'] = 'សុវត្ថិភាព';
$lang['kxm_TH']['SecurityAdmin']['MENUTITLE'] = 'សុវត្ថិភាព';
$lang['kxm_TH']['SecurityAdmin_left.ss']['ENABLEDRAGGING'] = 'ប្រើប្រាស់ការទាញទៅមកបាន';
$lang['kxm_TH']['SecurityAdmin_right.ss']['WELCOME1'] = 'សូមស្វាគមន៍ មកកាន់';
$lang['kxm_TH']['SecurityAdmin_right.ss']['WELCOME2'] = 'ផ្នែកគ្រប់គ្រងសុវត្ថិភាព។  សូមជ្រើរើសប្រអប់នៅខាងឆ្វេង។';
$lang['kxm_TH']['SideReport']['REPEMPTY'] = ' មិនមាន %s​ របាយការណ៍';

?>