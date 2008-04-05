<?php
/**
 * URL rules for the CMS module
 * 
 * @package cms
 */
Director::addRules(50, array(
	'processes/$Action/$ID/$Batch' => 'BatchProcess_Controller',
	'silverstripe' => '->admin',
	'cms' => '->admin',
	'admin/security/$Action/$ID/$OtherID' => 'SecurityAdmin',
	'admin/help/$Action/$ID' => 'CMSHelp',
	'admin/newsletter/$Action/$ID' => 'NewsletterAdmin',
	'admin/reports/$Action/$ID' => 'ReportAdmin',
	'admin/assets/$Action/$ID' => 'AssetAdmin',
	'admin/comments/$Action' => 'CommentAdmin',
	'admin/ReportField/$Action/$ID/$Type/$OtherID' => 'ReportField_Controller',
	'admin/bulkload/$Action/$ID/$OtherID' => 'BulkLoaderAdmin',
	'admin/ImageEditor/$Action' => 'ImageEditor',
	'admin/$Action/$ID/$OtherID' => 'CMSMain',
	'unsubscribe/$Email/$MailingList' => 'Unsubscribe_Controller'
));

// Built-in modules
LeftAndMain::add_menu_item(
	"content",
	_t('LeftAndMain.SITECONTENT',"Site Content",PR_HIGH,"Menu title"),
	"admin/",
	"CMSMain"
);
LeftAndMain::add_menu_item(
	"files",
	_t('LeftAndMain.FILESIMAGES',"Files & Images",PR_HIGH,"Menu title"),
	"admin/assets/", 
	"AssetAdmin"
);
LeftAndMain::add_menu_item(
	"newsletter", 
	_t('LeftAndMain.NEWSLETTERS',"Newsletters",PR_HIGH,"Menu title"),
	"admin/newsletter/", 
	"NewsletterAdmin"
);
if(ReportAdmin::has_reports()) {
	LeftAndMain::add_menu_item(
		"report", 
		_t('LeftAndMain.REPORTS',"Reports",PR_HIGH,'Menu title'),
		"admin/reports/", 
		"ReportAdmin"
	);
}
LeftAndMain::add_menu_item(
	"security", 
	_t('LeftAndMain.SECURITY',"Security",PR_HIGH,'Menu title'),
	"admin/security/", 
	"SecurityAdmin"
);
LeftAndMain::add_menu_item(
	"comments", 
	_t('LeftAndMain.COMMENTS',"Comments",PR_HIGH,'Menu title'),
	"admin/comments/", 
	"CommentAdmin"
);
LeftAndMain::add_menu_item(
	"help",	
	_t('LeftAndMain.HELP',"Help",PR_HIGH,'Menu title'), 
	"http://userhelp.silverstripe.com"
);

?>