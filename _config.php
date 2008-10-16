<?php
/**
 * URL rules for the CMS module
 * 
 * @package cms
 */
Director::addRules(50, array(
	'processes//$Action/$ID/$Batch' => 'BatchProcess_Controller',
	'silverstripe' => '->admin',
	'cms' => '->admin',
	'admin/security//$Action/$ID/$OtherID' => 'SecurityAdmin',
	'admin/help//$Action/$ID' => 'CMSHelp',
	'admin/reports//$Action/$ID' => 'ReportAdmin',
	'admin/assets//$Action/$ID' => 'AssetAdmin',
	'admin/comments//$Action' => 'CommentAdmin',
	'admin/ReportField//$Action/$ID/$Type/$OtherID' => 'ReportField_Controller',
	'admin/bulkload//$Action/$ID/$OtherID' => 'BulkLoaderAdmin',
	'admin//ImageEditor/$Action' => 'ImageEditor',
	'admin/cms//$Action/$ID/$OtherID' => 'CMSMain',
	'admin//$Action/$ID/$OtherID' => 'CMSMain',
	'PageComment//$Action/$ID' => 'PageComment_Controller',
	'dev/buildcache' => 'RebuildStaticCacheTask',
));

// Built-in modules
LeftAndMain::populate_default_menu();

// If there are reports, add the ReportAdmin tab in CMS
if(ReportAdmin::has_reports()) {
	LeftAndMain::add_menu_item(
		'reports', 
		_t('LeftAndMain.REPORTS', 'Reports', PR_HIGH, 'Menu title'),
		'admin/reports/', 
		'ReportAdmin'
	);
}

?>
