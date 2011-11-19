<?php

/**
 * Thai (Thailand) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('th_TH', $lang) && is_array($lang['th_TH'])) {
	$lang['th_TH'] = array_merge($lang['en_US'], $lang['th_TH']);
} else {
	$lang['th_TH'] = $lang['en_US'];
}

$lang['th_TH']['AssetAdmin']['CHOOSEFILE'] = 'เลือกไฟล์';
$lang['th_TH']['AssetAdmin']['FILESREADY'] = 'ไฟล์พร้อมสำหรับอัปโหลด';
$lang['th_TH']['AssetAdmin']['MENUTITLE'] = 'ไฟล์ และ รูปภาพ';
$lang['th_TH']['AssetAdmin']['MENUTITLE'] = 'ไฟล์และภาพ';
$lang['th_TH']['AssetAdmin']['MOVEDX'] = 'ย้าย %s ไฟล์';
$lang['th_TH']['AssetAdmin']['NOTHINGTOUPLOAD'] = 'ไม่มีไฟล์สำหรับอัปโหลด';
$lang['th_TH']['AssetAdmin']['SAVEFOLDERNAME'] = 'บันทึกชื่อโฟลเดอร์';
$lang['th_TH']['AssetAdmin']['UPLOAD'] = 'อัปโหลดไฟล์ที่แสดงรายการด้านล่าง';
$lang['th_TH']['AssetAdmin']['UPLOADEDX'] = 'อัปโหลด %s ไฟล์';
$lang['th_TH']['AssetAdmin_left.ss']['CREATE'] = 'สร้าง';
$lang['th_TH']['AssetAdmin_left.ss']['DELETE'] = 'ลบ';
$lang['th_TH']['AssetAdmin_left.ss']['DELFOLDERS'] = 'ลบโฟลเดอร์ที่เลือกไว้';
$lang['th_TH']['AssetAdmin_left.ss']['ENABLEDRAGGING'] = 'อนุญาต ลาก และ วาง เรียงลำดับ';
$lang['th_TH']['AssetAdmin_left.ss']['FOLDERS'] = 'โฟลเดอร์';
$lang['th_TH']['AssetAdmin_right.ss']['CHOOSEPAGE'] = 'กรุณาเลือกหน้าจากด้านซ้ายมือ';
$lang['th_TH']['AssetAdmin_right.ss']['WELCOME'] = 'ยินดีตอนรับสู่';
$lang['th_TH']['AssetAdmin_uploadiframe.ss']['PERMFAILED'] = 'คุณไม่มีสิทธิ์ในการอัปโหลดไฟล์เข้าสู่โฟลเดอร์นี้';
$lang['th_TH']['AssetTableField']['DIM'] = 'ความสูงความยาว';
$lang['th_TH']['AssetTableField']['FILENAME'] = 'ชื่อไฟล์';
$lang['th_TH']['AssetTableField']['LASTEDIT'] = 'เปลี่ยนแปลงล่าสุด';
$lang['th_TH']['AssetTableField']['OWNER'] = 'เจ้าของ';
$lang['th_TH']['AssetTableField']['SIZE'] = 'ขนาด';
$lang['th_TH']['AssetTableField.ss']['DELFILE'] = 'ลบไฟล์นี้';
$lang['th_TH']['AssetTableField.ss']['DRAGTOFOLDER'] = 'ลากไปยังโฟลเดอร์ด้านซ้ายมือเพื่อย้ายไฟล์';
$lang['th_TH']['AssetTableField']['TITLE'] = 'ชื่อเรื่อง';
$lang['th_TH']['AssetTableField']['TYPE'] = 'ชนิด';
$lang['th_TH']['CMSMain']['ACCESS'] = 'เข้าถึงส่วน \'%s\'';
$lang['th_TH']['CMSMain']['CANCEL'] = 'ยกเลิก';
$lang['th_TH']['CMSMain']['CHOOSEREPORT'] = '(เลือกรายงาน)';
$lang['th_TH']['CMSMain']['EMAIL'] = 'อีเมล์';
$lang['th_TH']['CMSMain']['GO'] = 'ไป';
$lang['th_TH']['CMSMain']['MENUTITLE'] = 'เนื้อหา';
$lang['th_TH']['CMSMain']['MENUTITLE'] = 'เนื้อหาเว็บไซต์';
$lang['th_TH']['CMSMain']['NEW'] = 'ข่าว';
$lang['th_TH']['CMSMain']['NOCONTENT'] = 'ไม่มีเนื้อหา';
$lang['th_TH']['CMSMain']['OK'] = 'ตกลง';
$lang['th_TH']['CMSMain']['PAGENOTEXISTS'] = 'ไม่มีหน้านี้อยู่';
$lang['th_TH']['CMSMain']['PRINT'] = 'พิมพ์';
$lang['th_TH']['CMSMain']['PUBPAGES'] = 'เผยแพร่ %d หน้า';
$lang['th_TH']['CMSMain']['REMOVEDFD'] = 'ลบออกจากไซต์แบบร่างแล้ว';
$lang['th_TH']['CMSMain']['RESTORED'] = 'การเรียกคืน สำเร็จ';
$lang['th_TH']['CMSMain']['SAVE'] = 'บันทึก';
$lang['th_TH']['CMSMain']['TOTALPAGES'] = 'เอกสารทั้งหมด';
$lang['th_TH']['CMSMain']['VERSIONSNOPAGE'] = 'ไม่พบหน้า #%d';
$lang['th_TH']['CMSMain']['VIEWING'] = 'คุณกำลังดูเวอร์ชัน #%s, สร้างเมื่อ %s โดย %s';
$lang['th_TH']['CMSMain_left.ss']['BATCHACTIONS'] = 'การกระทำแบบกลุ่ม';
$lang['th_TH']['CMSMain_left.ss']['CHANGED'] = 'เปลี่ยนแปลงแล้ว';
$lang['th_TH']['CMSMain_left.ss']['CLOSEBOX'] = 'คลิกเพื่อปิดกล่องนี้';
$lang['th_TH']['CMSMain_left.ss']['CREATE'] = 'สร้าง';
$lang['th_TH']['CMSMain_left.ss']['DEL'] = 'ลบ';
$lang['th_TH']['CMSMain_left.ss']['DELETECONFIRM'] = 'ลบหน้าที่เลือกไว้';
$lang['th_TH']['CMSMain_left.ss']['EDITEDNOTPUB'] = 'แก้ไขไซต์โครงร่างและข้อมูลที่ยังไม่ได้แสดง';
$lang['th_TH']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'อนุญาต ลาก และ วาง เรียงลำดับ';
$lang['th_TH']['CMSMain_left.ss']['GO'] = 'ไป';
$lang['th_TH']['CMSMain_left.ss']['NEW'] = 'ใหม่';
$lang['th_TH']['CMSMain_left.ss']['OPENBOX'] = 'คลิกเพื่อเปิดกล่องนี้';
$lang['th_TH']['CMSMain_left.ss']['PUBLISHCONFIRM'] = 'แสดงหน้าที่เลือกไว้';
$lang['th_TH']['CMSMain_left.ss']['SEARCH'] = 'ค้นหา';
$lang['th_TH']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'เนื้อหา และ โครงสร้าง';
$lang['th_TH']['CMSMain_left.ss']['SITEREPORTS'] = 'รายงานเว็บไซต์';
$lang['th_TH']['CMSMain_right.ss']['CHOOSEPAGE'] = 'กรุณาเลือกหน้าจากทางด้านซ้ายมือ';
$lang['th_TH']['CMSMain_right.ss']['WELCOMETO'] = 'ยินดีตอ้นรับสู่';
$lang['th_TH']['CMSMain_versions.ss']['AUTHOR'] = 'ผู้เขียน';
$lang['th_TH']['CMSMain_versions.ss']['UNKNOWN'] = 'ไม่ทราบ';
$lang['th_TH']['CMSMain_versions.ss']['WHEN'] = 'เมื่อ';
$lang['th_TH']['CommentAdmin']['MENUTITLE'] = 'หมายเหตุ';
$lang['th_TH']['CommentAdmin']['MENUTITLE'] = 'ความคิดเห็น';
$lang['th_TH']['ImageEditor.ss']['CANCEL'] = 'ยกเลิก';
$lang['th_TH']['ImageEditor.ss']['CROP'] = 'ตัดครอบ';
$lang['th_TH']['ImageEditor.ss']['HEIGHT'] = 'สูง';
$lang['th_TH']['ImageEditor.ss']['REDO'] = 'ทำซ้ำ';
$lang['th_TH']['ImageEditor.ss']['ROT'] = 'หมุน';
$lang['th_TH']['ImageEditor.ss']['UNDO'] = 'ยกเลิกทำ';
$lang['th_TH']['ImageEditor.ss']['WIDTH'] = 'กว้าง';
$lang['th_TH']['LeftAndMain']['CHANGEDURL'] = 'เปลี่ยน URL เป็น \'%s\'';
$lang['th_TH']['LeftAndMain']['HELP'] = 'ช่วยเหลือ';
$lang['th_TH']['LeftAndMain']['PAGETYPE'] = 'ประเภทหน้า:';
$lang['th_TH']['LeftAndMain']['SAVED'] = 'บันทึก';
$lang['th_TH']['LeftAndMain']['SAVEDUP'] = 'บันทึก';
$lang['th_TH']['LeftAndMain']['SITECONTENTLEFT'] = 'เนื้อหา ภายใน';
$lang['th_TH']['LeftAndMain.ss']['APPVERSIONTEXT1'] = 'นี่คือ';
$lang['th_TH']['LeftAndMain.ss']['ARCHS'] = 'ไซต์ที่เก็บบันทึกไว้';
$lang['th_TH']['LeftAndMain.ss']['DRAFTS'] = 'ไซต์แบบร่าง';
$lang['th_TH']['LeftAndMain.ss']['EDIT'] = 'แก้ไข';
$lang['th_TH']['LeftAndMain.ss']['LOADING'] = 'กำลังทำงาน ...';
$lang['th_TH']['LeftAndMain.ss']['LOGGEDINAS'] = 'เข้าสู่ระบบในชื่อ';
$lang['th_TH']['LeftAndMain.ss']['LOGOUT'] = 'ออกจากระบบ';
$lang['th_TH']['LeftAndMain.ss']['PUBLIS'] = 'ไซต์แสดงผล';
$lang['th_TH']['LeftAndMain.ss']['REQUIREJS'] = 'ระบบ CMS ต้องการ ให้เปิดการใช้งาน JavaScript';
$lang['th_TH']['LeftAndMain.ss']['SSWEB'] = 'เวบไซต์ Silverstripe';
$lang['th_TH']['LeftAndMain']['STATUSPUBLISHEDSUCCESS'] = 'เผยแพร่ \'%s\' สำเร็จ';
$lang['th_TH']['LeftAndMain']['STATUSTO'] = 'เปลี่ยนสถานะเป็น \'%s\'';
$lang['th_TH']['MemberList.ss']['FILTER'] = 'กรอง';
$lang['th_TH']['MemberList_Table.ss']['EMAIL'] = 'อีเมล์';
$lang['th_TH']['MemberList_Table.ss']['FN'] = 'ชื่อ';
$lang['th_TH']['MemberList_Table.ss']['PASSWD'] = 'รหัสผ่าน';
$lang['th_TH']['MemberList_Table.ss']['SN'] = 'นามสกุล';
$lang['th_TH']['MemberTableField']['ADD'] = 'เพิ่ม';
$lang['th_TH']['MemberTableField']['ADDEDTOGROUP'] = 'เพิ่มสมาชิกเข้ากลุ่ม';
$lang['th_TH']['MemberTableField']['DeleteTitleText'] = 'ลบออกจากกลุ่มนี้';
$lang['th_TH']['MemberTableField']['DeleteTitleTextDatabase'] = 'ลบออกจากฐานข้อมูลและทุกกลุ่ม';
$lang['th_TH']['MemberTableField.ss']['ADDNEW'] = 'เพิ่ม';
$lang['th_TH']['ModelAdmin']['CREATEBUTTON'] = 'สร้าง \'%s\'';
$lang['th_TH']['PageComment']['COMMENTBY'] = 'ความเห็นโดย \'%s\' เมื่อ %s';
$lang['th_TH']['PageComment']['PLURALNAME'] = 'หน้าความเห็น';
$lang['th_TH']['PageComment']['SINGULARNAME'] = 'หน้าความเห็น';
$lang['th_TH']['PageCommentInterface.ss']['POSTCOM'] = 'แสดงความเห็น';
$lang['th_TH']['ReportAdmin']['MENUTITLE'] = 'รายงาน';
$lang['th_TH']['ReportAdmin_left.ss']['REPORTS'] = 'รายงาน';
$lang['th_TH']['ReportAdmin_right.ss']['WELCOME1'] = 'ยินดีตอนรับสู่';
$lang['th_TH']['ReportAdmin_right.ss']['WELCOME2'] = 'ส่วน การทำรายงาน . กรุณาระบุ รายงานจากด้านซ้ายมือ';
$lang['th_TH']['ReportAdmin_SiteTree.ss']['REPORTS'] = 'รายงาน';
$lang['th_TH']['SecurityAdmin']['MENUTITLE'] = 'ความปลอดภัย';
$lang['th_TH']['SecurityAdmin']['MENUTITLE'] = 'รักษาความปลอดภัย';
$lang['th_TH']['SecurityAdmin']['SAVE'] = 'บันทึก';
$lang['th_TH']['SecurityAdmin_left.ss']['CREATE'] = 'สร้าง';
$lang['th_TH']['SecurityAdmin_left.ss']['DEL'] = 'ลบ';
$lang['th_TH']['SecurityAdmin_left.ss']['DELGROUPS'] = 'ลบกลุ่มที่เลือกไว้';
$lang['th_TH']['SecurityAdmin_left.ss']['ENABLEDRAGGING'] = 'อนุญาต ลาก และ วาง เรียงลำดับ';
$lang['th_TH']['SecurityAdmin_left.ss']['GO'] = 'ไป';
$lang['th_TH']['SecurityAdmin_left.ss']['SECGROUPS'] = 'กลุ่มความปลอดภัย';
$lang['th_TH']['SecurityAdmin_right.ss']['WELCOME1'] = 'ยินดีตอนรับสู่';
$lang['th_TH']['SecurityAdmin_right.ss']['WELCOME2'] = 'ระบบ ผู้ดูแลระบบ การจัดการความปลอกภัย . กรุณาเลือกกลุ่ม จากทางด้านซ้ายมือ';
$lang['th_TH']['SideReport']['REPEMPTY'] = 'รายงาน ว่างเปล่า';

?>