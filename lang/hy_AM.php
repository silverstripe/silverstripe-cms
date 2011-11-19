<?php

/**
 * Armenian (Armenia) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('hy_AM', $lang) && is_array($lang['hy_AM'])) {
	$lang['hy_AM'] = array_merge($lang['en_US'], $lang['hy_AM']);
} else {
	$lang['hy_AM'] = $lang['en_US'];
}

$lang['hy_AM']['AssetAdmin']['MENUTITLE'] = 'Նշոցներ և Պատկերներ';
$lang['hy_AM']['AssetAdmin']['MENUTITLE'] = 'Նշոցներ և Պատկերներ';
$lang['hy_AM']['AssetAdmin_left.ss']['ENABLEDRAGGING'] = 'Թույլատրել քարշելով և ընկնելով վերադասավորում';
$lang['hy_AM']['CMSMain']['MENUTITLE'] = 'Կայքի Պարունակություն';
$lang['hy_AM']['CMSMain']['MENUTITLE'] = 'Կայքի Պարունակություն';
$lang['hy_AM']['CMSMain']['NEW'] = 'Նոր';
$lang['hy_AM']['CMSMain']['RESTORED'] = 'Հաջող վերակառուցեց \'%s\'';
$lang['hy_AM']['CMSMain_left.ss']['BATCHACTIONS'] = 'Խմբային Գործողություններ';
$lang['hy_AM']['CMSMain_left.ss']['CREATE'] = 'Ստեղծել';
$lang['hy_AM']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'Թույլատրել քարշելով և ընկնելով վերադասավորում';
$lang['hy_AM']['CMSMain_left.ss']['SEARCH'] = 'Որոնել';
$lang['hy_AM']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Կայքի Պարունակություն և Կառուցվածք';
$lang['hy_AM']['CommentAdmin']['MENUTITLE'] = 'Դիտողություննէր';
$lang['hy_AM']['CommentAdmin']['MENUTITLE'] = 'Դիտողություննէր';
$lang['hy_AM']['LeftAndMain']['HELP'] = 'Օգնություն';
$lang['hy_AM']['LeftAndMain']['SITECONTENTLEFT'] = 'Կայքի Պարունակություն';
$lang['hy_AM']['LeftAndMain.ss']['LOADING'] = 'Բեռնում...';
$lang['hy_AM']['MemberList.ss']['FILTER'] = 'Զտել';
$lang['hy_AM']['MemberTableField.ss']['ADDNEW'] = 'Ավելացնել Նոր';
$lang['hy_AM']['PageComment']['PLURALNAME'] = 'Էչի Դիտողություննէր';
$lang['hy_AM']['PageComment']['SINGULARNAME'] = 'Էչի Դիտողություննէր';
$lang['hy_AM']['ReportAdmin']['MENUTITLE'] = 'Զեկույցներ';
$lang['hy_AM']['ReportAdmin_right.ss']['WELCOME1'] = 'Բարի գալուստ';
$lang['hy_AM']['ReportAdmin_right.ss']['WELCOME2'] = 'ռեպորտաժի բաժինը: Ընտրիր առանձնահատուկ զեկույց ձախից:';
$lang['hy_AM']['SecurityAdmin']['MENUTITLE'] = 'Անվտանգություն';
$lang['hy_AM']['SecurityAdmin']['MENUTITLE'] = 'Անվտանգություն';
$lang['hy_AM']['SecurityAdmin_left.ss']['ENABLEDRAGGING'] = 'Թույլատրել քարշելով և ընկնելով վերադասավորում';
$lang['hy_AM']['SecurityAdmin_right.ss']['WELCOME1'] = 'Բարի գալուստ';
$lang['hy_AM']['SecurityAdmin_right.ss']['WELCOME2'] = 'անվտանգության վարչական բաժինը: Ընտրիր խումբ ձախից:';
$lang['hy_AM']['SideReport']['REPEMPTY'] = '%s զեկույցը դատարկ է:';

?>