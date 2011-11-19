<?php

/**
 * Vietnamese (United States) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('vi_US', $lang) && is_array($lang['vi_US'])) {
	$lang['vi_US'] = array_merge($lang['en_US'], $lang['vi_US']);
} else {
	$lang['vi_US'] = $lang['en_US'];
}

$lang['vi_US']['AssetAdmin']['MENUTITLE'] = 'Tài liệu và hình ảnh';
$lang['vi_US']['AssetAdmin_left.ss']['ENABLEDRAGGING'] = 'Cho phép kéo thả để sắp xếp';
$lang['vi_US']['CMSMain']['MENUTITLE'] = 'Nội dung website';
$lang['vi_US']['CMSMain']['MENUTITLE'] = 'Trang';
$lang['vi_US']['CMSMain']['NEW'] = 'Mới';
$lang['vi_US']['CMSMain_left.ss']['CREATE'] = 'Tạo';
$lang['vi_US']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'Cho phép kéo thả để sắp xếp';
$lang['vi_US']['CMSMain_left.ss']['SEARCH'] = 'Tìm kiếm';
$lang['vi_US']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Cây thư mục';
$lang['vi_US']['CommentAdmin']['MENUTITLE'] = 'Bình luận';
$lang['vi_US']['CommentAdmin']['MENUTITLE'] = 'Bình luận';
$lang['vi_US']['LeftAndMain']['HELP'] = 'Giúp đở';
$lang['vi_US']['LeftAndMain']['SITECONTENTLEFT'] = 'Nội dung website';
$lang['vi_US']['LeftAndMain.ss']['LOADING'] = 'Đang tải...';
$lang['vi_US']['MemberTableField.ss']['ADDNEW'] = 'Thêm mới';
$lang['vi_US']['ModelAdmin']['CREATEBUTTON'] = 'Tạo \'%s\'';
$lang['vi_US']['PageComment']['PLURALNAME'] = 'Bình luận';
$lang['vi_US']['PageComment']['SINGULARNAME'] = 'Bình luận';
$lang['vi_US']['ReportAdmin']['MENUTITLE'] = 'Báo cáo';
$lang['vi_US']['SecurityAdmin_left.ss']['ENABLEDRAGGING'] = 'Cho phép kéo thả để sắp xếp';

?>