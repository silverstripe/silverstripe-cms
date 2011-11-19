<?php

/**
 * Uzbek (Uzbekistan) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('uz_UZ', $lang) && is_array($lang['uz_UZ'])) {
	$lang['uz_UZ'] = array_merge($lang['en_US'], $lang['uz_UZ']);
} else {
	$lang['uz_UZ'] = $lang['en_US'];
}

$lang['uz_UZ']['AssetAdmin']['MENUTITLE'] = 'Fayllar va Rasmlar';
$lang['uz_UZ']['CMSMain']['CANCEL'] = 'Bekor qilish';
$lang['uz_UZ']['CMSMain']['CHOOSEREPORT'] = '(Hisobotni tanlang)';
$lang['uz_UZ']['CMSMain']['EMAIL'] = 'Email';
$lang['uz_UZ']['CMSMain']['GO'] = 'Boshla';
$lang['uz_UZ']['CMSMain']['MENUTITLE'] = 'Saytdagi ma\'lumotlar';
$lang['uz_UZ']['CMSMain']['NEW'] = 'Yangi';
$lang['uz_UZ']['CMSMain']['NOCONTENT'] = 'hech nima yoq';
$lang['uz_UZ']['CMSMain']['OK'] = 'Ok';
$lang['uz_UZ']['CMSMain']['PAGENOTEXISTS'] = 'Bu sahifa mavjud emas.';
$lang['uz_UZ']['CMSMain']['PRINT'] = 'Printerdan chiqarish';
$lang['uz_UZ']['CMSMain']['PUBALLCONFIRM'] = 'Iltimos, saytdagi bor hamma sahifalarni chop eting. ';
$lang['uz_UZ']['CMSMain']['PUBALLFUN'] = '"Hammasi chop etish" funktsiyasi';
$lang['uz_UZ']['CMSMain']['PUBALLFUN2'] = 'Bu tugmani bosish bilan siz saytda bor bo\'lgan hamma sahifalar chop etgan bo\'lasiz.';
$lang['uz_UZ']['CMSMain']['PUBPAGES'] = 'Tayyor: %d ta sahifa chop etildi';
$lang['uz_UZ']['CMSMain']['RESTORED'] = '\'%s\' qayta tiklandi';
$lang['uz_UZ']['CMSMain']['ROLLEDBACKVERSION'] = 'Orga versiyaga qaytarildi #%d. Yangi versiya #%d';
$lang['uz_UZ']['CMSMain']['SAVE'] = 'Saqlash';
$lang['uz_UZ']['CMSMain']['TOTALPAGES'] = 'Jami sahifalar:';
$lang['uz_UZ']['CMSMain']['VERSIONSNOPAGE'] = '#%d sahifasi topilmadi';
$lang['uz_UZ']['CMSMain_left.ss']['BATCHACTIONS'] = 'Birgalikda harakat';
$lang['uz_UZ']['CMSMain_left.ss']['CREATE'] = 'Yangi';
$lang['uz_UZ']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'Surish yoli bilan tahlash';
$lang['uz_UZ']['CMSMain_left.ss']['SEARCH'] = 'Qidiruv';
$lang['uz_UZ']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Sayt strukturasi';
$lang['uz_UZ']['CommentAdmin']['MENUTITLE'] = 'Izohlar';
$lang['uz_UZ']['LeftAndMain']['CHANGEDURL'] = 'URL manzili o\'zgardi \'%s\'';
$lang['uz_UZ']['LeftAndMain']['HELP'] = 'Yordam';
$lang['uz_UZ']['LeftAndMain']['PAGETYPE'] = 'Sahifa turi:';
$lang['uz_UZ']['LeftAndMain']['PERMAGAIN'] = 'Siz saytdan chiqdingiz.';
$lang['uz_UZ']['LeftAndMain']['PERMALREADY'] = 'Bu erdan saytga kirishingiz mumkin';
$lang['uz_UZ']['LeftAndMain']['SAVEDUP'] = 'Saqlandi';
$lang['uz_UZ']['LeftAndMain']['SITECONTENTLEFT'] = 'Sayt ma\'lumotlari';
$lang['uz_UZ']['LeftAndMain.ss']['LOADING'] = 'Ochilmoqda..';
$lang['uz_UZ']['MemberList.ss']['FILTER'] = 'Filtr';
$lang['uz_UZ']['MemberTableField.ss']['ADDNEW'] = 'Yang qo\'shish';
$lang['uz_UZ']['ReportAdmin_right.ss']['WELCOME1'] = ' ga hush kelibsiz';
$lang['uz_UZ']['ReportAdmin_right.ss']['WELCOME2'] = 'hisobotlar bo\'limi. Iltimos chap tarafdan kerakli hisobotni tanlang';
$lang['uz_UZ']['SecurityAdmin']['MENUTITLE'] = 'Havfsizlik';
$lang['uz_UZ']['SecurityAdmin_right.ss']['WELCOME1'] = ' ga hush kelibsiz';
$lang['uz_UZ']['SecurityAdmin_right.ss']['WELCOME2'] = 'havfsizlik administratori bo\'limi. Iltimos chap tarafdan guruhni tanlang';
$lang['uz_UZ']['SideReport']['REPEMPTY'] = '%s hisobot bo\'m bo\'sh';

?>