<?php

/**
 * Khmer (Cambodia) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('km_KH', $lang) && is_array($lang['km_KH'])) {
	$lang['km_KH'] = array_merge($lang['en_US'], $lang['km_KH']);
} else {
	$lang['km_KH'] = $lang['en_US'];
}

$lang['km_KH']['AssetAdmin']['MENUTITLE'] = 'រូបភាព និងឯកសារ';
$lang['km_KH']['AssetAdmin']['MENUTITLE'] = 'រូបភាព និងឯកសារ';
$lang['km_KH']['AssetAdmin_left.ss']['ENABLEDRAGGING'] = 'ប្រើប្រាស់ការទាញដាក់';
$lang['km_KH']['CMSMain']['ACCESS'] = 'ចូលទៅកាន់ផ្នែក \'%s\'';
$lang['km_KH']['CMSMain']['CHOOSEREPORT'] = '(សូមជ្រើសរសើរបាយការណ៍)';
$lang['km_KH']['CMSMain']['EMAIL'] = 'អ៊ីមែល​';
$lang['km_KH']['CMSMain']['GO'] = 'ទៅ​';
$lang['km_KH']['CMSMain']['MENUTITLE'] = 'ទំរង់អត្ថបទ';
$lang['km_KH']['CMSMain']['MENUTITLE'] = 'ទំព័រ';
$lang['km_KH']['CMSMain']['METAKEYWORDSOPT'] = 'ពាក្យគន្លិះ';
$lang['km_KH']['CMSMain']['NEW'] = 'ថ្មី';
$lang['km_KH']['CMSMain']['PAGENOTEXISTS'] = 'ទំព័រនេះមិនមានទេ';
$lang['km_KH']['CMSMain']['REMOVEDFD'] = 'បានលប់ចេញពីទំព័រប្រៀង';
$lang['km_KH']['CMSMain']['REMOVEDPAGE'] = 'ទំព័រ \'%s\' បានដកចេញពីគេហទំព័រ';
$lang['km_KH']['CMSMain']['RESTORED'] = '\'%s" បានដាក់ចូលវិញ';
$lang['km_KH']['CMSMain']['ROLLEDBACKPUB'] = 'ត្រលប់ទៅកាន់ទំព័រធ្វើរួច។ សេរីថ្មីគឺ #%d';
$lang['km_KH']['CMSMain']['ROLLEDBACKVERSION'] = 'ត្រលប់ទៅកាន់សេរី #%d. លេខសេរីថ្មីគឺ #%d';
$lang['km_KH']['CMSMain']['SAVE'] = 'រក្សា​ទុក​';
$lang['km_KH']['CMSMain']['VERSIONSNOPAGE'] = 'មិន​អាច​រក​ទំព័រ​';
$lang['km_KH']['CMSMain']['VIEWING'] = 'អ្នកកំពុងមើល ជំនាន់ #%s​, បង្កើត %s ដោយ %s';
$lang['km_KH']['CMSMain_left.ss']['BATCHACTIONS'] = 'សកម្មភាពរួម';
$lang['km_KH']['CMSMain_left.ss']['CREATE'] = 'បង្កើត';
$lang['km_KH']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'ប្រើប្រាស់ការទាញដាក់';
$lang['km_KH']['CMSMain_left.ss']['SEARCH'] = 'ស្វែងរក';
$lang['km_KH']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'សម្ព័ន្ធទំព័រ';
$lang['km_KH']['CommentAdmin']['MENUTITLE'] = 'យោបល់';
$lang['km_KH']['CommentAdmin']['MENUTITLE'] = 'យោបល់';
$lang['km_KH']['LeftAndMain']['HELP'] = 'ជំនួយការ';
$lang['km_KH']['LeftAndMain']['SITECONTENTLEFT'] = 'ទំរង់អត្ថបទ';
$lang['km_KH']['LeftAndMain.ss']['LOADING'] = 'កំពុងដំណើរការ...';
$lang['km_KH']['LeftAndMain.ss']['REQUIREJS'] = 'CMS ត្រូវការអ្នកប្រើប្រាស់ Javascript';
$lang['km_KH']['MemberList.ss']['FILTER'] = 'ជំរើស';
$lang['km_KH']['MemberTableField.ss']['ADDNEW'] = 'បញ្ចូលថ្មី';
$lang['km_KH']['ModelAdmin']['CREATEBUTTON'] = 'បង្កើត \'%s\'';
$lang['km_KH']['PageComment']['COMMENTBY'] = 'យោបល់ដោយ \'%s\' នៅ %s';
$lang['km_KH']['PageComment']['PLURALNAME'] = 'យោបល់ក្នុងទំព័រទាំងអស់';
$lang['km_KH']['PageComment']['SINGULARNAME'] = 'យោបល់ក្នុងទំព័រ';
$lang['km_KH']['ReportAdmin']['MENUTITLE'] = 'របាយការណ៍';
$lang['km_KH']['ReportAdmin_right.ss']['WELCOME1'] = 'សូមស្វាគមន៍មកកាន់';
$lang['km_KH']['ReportAdmin_right.ss']['WELCOME2'] = 'ផ្នែកគ្រប់គ្រងរបាយការណ៍។ សូមជ្រើរើសប្រអប់នៅខាងឆ្វេង។';
$lang['km_KH']['SecurityAdmin']['MENUTITLE'] = 'សុវត្ថិភាព';
$lang['km_KH']['SecurityAdmin']['MENUTITLE'] = 'សុវត្ថិភាព';
$lang['km_KH']['SecurityAdmin_left.ss']['ENABLEDRAGGING'] = 'ប្រើប្រាស់ការទាញដាក់';
$lang['km_KH']['SecurityAdmin_right.ss']['WELCOME1'] = 'សូមស្វាគមន៍មកកាន់';
$lang['km_KH']['SecurityAdmin_right.ss']['WELCOME2'] = 'ផ្នែកគ្រប់គ្រងសុវត្ថិភាព។ សូមជ្រើរើសក្រុមនៅខាងឆ្វេង។';
$lang['km_KH']['SideReport']['REPEMPTY'] = 'របាយការណ៍ %s ទទេ';

?>