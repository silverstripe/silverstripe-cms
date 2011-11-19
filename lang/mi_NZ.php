<?php

/**
 * Maori (New Zealand) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('mi_NZ', $lang) && is_array($lang['mi_NZ'])) {
	$lang['mi_NZ'] = array_merge($lang['en_US'], $lang['mi_NZ']);
} else {
	$lang['mi_NZ'] = $lang['en_US'];
}

$lang['mi_NZ']['AssetAdmin']['MENUTITLE'] = 'Ngā kōnae me ngā atahanga. ';
$lang['mi_NZ']['AssetAdmin_left.ss']['CREATE'] = 'Tawhito';
$lang['mi_NZ']['AssetAdmin_left.ss']['GO'] = 'Haere';
$lang['mi_NZ']['AssetTableField']['DIM'] = 'Nuinga';
$lang['mi_NZ']['AssetTableField']['OWNER'] = 'Kaiūmanga';
$lang['mi_NZ']['AssetTableField']['SIZE'] = 'Nuinga';
$lang['mi_NZ']['AssetTableField']['TITLE'] = 'Ingoa ';
$lang['mi_NZ']['AssetTableField']['TYPE'] = 'Tūmomo ';
$lang['mi_NZ']['CMSMain']['CANCEL'] = 'Whakakorea';
$lang['mi_NZ']['CMSMain']['CHOOSEREPORT'] = '(Kōwhiria he pūrongo)';
$lang['mi_NZ']['CMSMain']['COMPARINGV'] = 'Taurite ana koe i te putanga #%d ki te putanga #%d.';
$lang['mi_NZ']['CMSMain']['COPYPUBTOSTAGE'] = ' ';
$lang['mi_NZ']['CMSMain']['EMAIL'] = 'Īmēra';
$lang['mi_NZ']['CMSMain']['GO'] = 'Haere';
$lang['mi_NZ']['CMSMain']['MENUTITLE'] = 'Wāhi kai';
$lang['mi_NZ']['CMSMain']['NEW'] = 'he ____ hōu';
$lang['mi_NZ']['CMSMain']['NOCONTENT'] = 'Korekau he kiko.';
$lang['mi_NZ']['CMSMain']['OK'] = 'ĀE';
$lang['mi_NZ']['CMSMain']['PAGENOTEXISTS'] = 'kāore tēnei rārangi e tīari';
$lang['mi_NZ']['CMSMain']['PRINT'] = 'Tāngia';
$lang['mi_NZ']['CMSMain']['PUBALLCONFIRM'] = 'Whakaputaina koa i ia whārangi i te pae me te tārua i te content stage kia ora ai.';
$lang['mi_NZ']['CMSMain']['PUBALLFUN'] = 'Te taumahinga "Whakaputaina Katoatia"';
$lang['mi_NZ']['CMSMain']['PUBALLFUN2'] = 'Ko te pēhi i tēnei pātene he taurite ki te haere ki ia whārangi me te pēhi i te "whakaputaina". Ko te tikanga, ka whakamahia tēnei pātene i muri ake i te mahi whakatikatika kua maha ngā tinihanga, pērā i te wā i hangaia tuatahi mai te pae. ';
$lang['mi_NZ']['CMSMain']['PUBPAGES'] = 'Kua oti: %d ngā whārangi kua whakaputaina.';
$lang['mi_NZ']['CMSMain']['REMOVEDFD'] = ' ';
$lang['mi_NZ']['CMSMain']['REMOVEDPAGE'] = 'Kua tangohia mai te \'%s\' i te pae kua oti kē te tā.';
$lang['mi_NZ']['CMSMain']['RESTORED'] = 'i whakaora pai ai te \'%s\'';
$lang['mi_NZ']['CMSMain']['ROLLBACK'] = 'Hoki whakamuri ki tēnei putanga.';
$lang['mi_NZ']['CMSMain']['ROLLEDBACKPUB'] = 'I hoki whakamuri atu ki te putanga i tāngia. Ko te tau putanga hou ko te #%d.';
$lang['mi_NZ']['CMSMain']['ROLLEDBACKVERSION'] = 'I hoki whakamuri atu ki te putanga #%d. Ko te tau putanga hou ko te #%d.';
$lang['mi_NZ']['CMSMain']['SAVE'] = 'tiakina';
$lang['mi_NZ']['CMSMain']['TOTALPAGES'] = 'Te katoa o ngā whārangi:';
$lang['mi_NZ']['CMSMain']['VERSIONSNOPAGE'] = 'Kāore te whārangi #%d e kitea.';
$lang['mi_NZ']['CMSMain']['VIEWING'] = 'Kei te titiro koe ki te putanga  #%d, i waihangatia i te';
$lang['mi_NZ']['CMSMain_left.ss']['CREATE'] = 'Waihangatia';
$lang['mi_NZ']['CMSMain_left.ss']['NEW'] = 'hou';
$lang['mi_NZ']['CMSMain_left.ss']['SEARCH'] = 'Rapunga';
$lang['mi_NZ']['CMSMain_right.ss']['CHOOSEPAGE'] = 'Tēnā, kōwhiria mai he whārangi i te taha mauī';
$lang['mi_NZ']['CMSMain_right.ss']['WELCOMETO'] = 'Nau mai ki';
$lang['mi_NZ']['CMSMain_versions.ss']['AUTHOR'] = 'Kaituhi ';
$lang['mi_NZ']['CMSMain_versions.ss']['PUBR'] = 'Kaiwhakaputa ';
$lang['mi_NZ']['CMSMain_versions.ss']['UNKNOWN'] = 'He hapa rereke';
$lang['mi_NZ']['CommentAdmin']['MENUTITLE'] = 'Korerotia';
$lang['mi_NZ']['LeftAndMain']['HELP'] = 'Āwhina';
$lang['mi_NZ']['LeftAndMain']['PAGETYPE'] = 'Momo whārangi:';
$lang['mi_NZ']['LeftAndMain']['PERMAGAIN'] = 'Kua takiputaina atu koe i te CMS. Ki te pīrangi koe ki te takiuru atu anō, tāurutia tētahi ingoa kaiwhakamahi me te kupuhipa. ';
$lang['mi_NZ']['LeftAndMain']['PERMALREADY'] = 'Aroha mai, kāore e taea te whakauru i tērā wāhanga o te CMS. Ki te pīrangi koe ki te takiuru atu mā tētahi atu ingoa, whakamahia ki raro nei.';
$lang['mi_NZ']['LeftAndMain']['PERMDEFAULT'] = 'Whiriwhiria koa tētahi aratuka motuhēhēnga me te tāuru i ō pūkenga ki te whakauru i te CMS.';
$lang['mi_NZ']['LeftAndMain']['SITECONTENTLEFT'] = 'Wāhi kai';
$lang['mi_NZ']['LeftAndMain.ss']['APPVERSIONTEXT1'] = 'Koinei te';
$lang['mi_NZ']['LeftAndMain.ss']['VIEWPAGEIN'] = 'Tirohia tenei whārangi anake';
$lang['mi_NZ']['MemberList.ss']['FILTER'] = 'Tātari ';
$lang['mi_NZ']['ReportAdmin_right.ss']['WELCOME1'] = 'Nau mai ki te';
$lang['mi_NZ']['SecurityAdmin']['MENUTITLE'] = 'Haumarutanga';
$lang['mi_NZ']['SecurityAdmin']['NEWGROUP'] = 'Roopu hou';
$lang['mi_NZ']['SecurityAdmin']['SAVE'] = 'Tiakina';
$lang['mi_NZ']['SecurityAdmin_right.ss']['WELCOME1'] = 'Nau mai ki te';

?>