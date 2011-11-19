<?php

/**
 * Vietnamese (Vietnam) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('vi_VN', $lang) && is_array($lang['vi_VN'])) {
	$lang['vi_VN'] = array_merge($lang['en_US'], $lang['vi_VN']);
} else {
	$lang['vi_VN'] = $lang['en_US'];
}

$lang['vi_VN']['AssetAdmin']['MENUTITLE'] = 'Tập tin & Hình ảnh';
$lang['vi_VN']['AssetAdmin_left.ss']['ENABLEDRAGGING'] = 'Dùng chức năng kéo thả';
$lang['vi_VN']['CMSMain']['CANCEL'] = 'Hủy';
$lang['vi_VN']['CMSMain']['CHOOSEREPORT'] = '(chọn báo cáo)';
$lang['vi_VN']['CMSMain']['COMPARINGV'] = 'So sánh phiên bản #%d và #%d';
$lang['vi_VN']['CMSMain']['EMAIL'] = 'Email';
$lang['vi_VN']['CMSMain']['MENUTITLE'] = 'Nội dung';
$lang['vi_VN']['CMSMain']['NEW'] = 'Trang ';
$lang['vi_VN']['CMSMain']['OK'] = 'Đồng ý';
$lang['vi_VN']['CMSMain']['PAGENOTEXISTS'] = 'Không tìm thấy trang này.';
$lang['vi_VN']['CMSMain']['PRINT'] = 'In';
$lang['vi_VN']['CMSMain']['REMOVEDFD'] = 'Xóa từ bản nháp';
$lang['vi_VN']['CMSMain']['REMOVEDPAGE'] = '"%s" đã được xóa khỏi trang hiện hành.';
$lang['vi_VN']['CMSMain']['RESTORED'] = 'Phục hồi trang "%s" thành công';
$lang['vi_VN']['CMSMain']['ROLLEDBACKPUB'] = 'Trở về phiên bản được xuất bản. Phiên bản mới là  #%d';
$lang['vi_VN']['CMSMain']['ROLLEDBACKVERSION'] = 'Trở về phiên bản #%d. Phiên bản mới là  #%d';
$lang['vi_VN']['CMSMain']['SAVE'] = 'Lưu';
$lang['vi_VN']['CMSMain']['VERSIONSNOPAGE'] = 'Không thể tìm thấy trang #%d';
$lang['vi_VN']['CMSMain']['VIEWING'] = 'Phiên bản #%d, tạo ngày %s';
$lang['vi_VN']['CMSMain_left.ss']['BATCHACTIONS'] = 'Thực hiện';
$lang['vi_VN']['CMSMain_left.ss']['CREATE'] = 'Tạo mới';
$lang['vi_VN']['CMSMain_left.ss']['DELETECONFIRM'] = 'Xóa các trang đã chọn';
$lang['vi_VN']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'Dùng chức năng kéo thả';
$lang['vi_VN']['CMSMain_left.ss']['NEW'] = 'mới';
$lang['vi_VN']['CMSMain_left.ss']['SEARCH'] = 'Tìm kiếm';
$lang['vi_VN']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Cấu trúc Website';
$lang['vi_VN']['CMSMain_right.ss']['CHOOSEPAGE'] = 'Hãy chọn một trang trong mục trái';
$lang['vi_VN']['CMSMain_right.ss']['WELCOMETO'] = 'Chao mừng đến';
$lang['vi_VN']['CommentAdmin']['MENUTITLE'] = 'Bình luận';
$lang['vi_VN']['CommentAdmin_SiteTree.ss']['COMMENTS'] = 'Bình luận';
$lang['vi_VN']['CommentAdmin_SiteTree.ss']['SPAM'] = 'Thư rác';
$lang['vi_VN']['LeftAndMain']['HELP'] = 'Trợ giúp';
$lang['vi_VN']['LeftAndMain']['PAGETYPE'] = 'Loại:';
$lang['vi_VN']['LeftAndMain']['PERMDEFAULT'] = 'Nhập email và mật khẩu để truy cập vào phần quản lý';
$lang['vi_VN']['LeftAndMain']['SAVED'] = 'đã lưu';
$lang['vi_VN']['LeftAndMain']['SAVEDUP'] = 'Đã lưu';
$lang['vi_VN']['LeftAndMain']['SITECONTENTLEFT'] = 'Website';
$lang['vi_VN']['LeftAndMain.ss']['LOADING'] = 'Đang tải ...';
$lang['vi_VN']['MemberList.ss']['FILTER'] = 'Lọc';
$lang['vi_VN']['MemberTableField.ss']['ADDNEW'] = 'Thêm mới';
$lang['vi_VN']['PageComment']['COMMENTBY'] = 'Bình luận bởi \'%s\' trên trang %s';
$lang['vi_VN']['PageCommentInterface_Controller']['SPAMQUESTION'] = 'Câu hỏi bảo vệ : %s';
$lang['vi_VN']['ReportAdmin_right.ss']['WELCOME1'] = 'Chào mừng';
$lang['vi_VN']['ReportAdmin_SiteTree.ss']['REPORTS'] = 'Báo cáo';
$lang['vi_VN']['SecurityAdmin']['MENUTITLE'] = 'Bảo mật';
$lang['vi_VN']['SecurityAdmin_left.ss']['ENABLEDRAGGING'] = 'Dùng chức năng kéo thả';
$lang['vi_VN']['SecurityAdmin_right.ss']['WELCOME1'] = 'Chào mừng ';
$lang['vi_VN']['SideReport']['REPEMPTY'] = 'Báo cáo "%s" rỗng';
$lang['vi_VN']['ViewArchivedEmail.ss']['HAVEASKED'] = 'Bạn được mời để xem nội dung của web chúng tôi trên';

?>