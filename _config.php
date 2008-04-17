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
	'admin/reports/$Action/$ID' => 'ReportAdmin',
	'admin/assets/$Action/$ID' => 'AssetAdmin',
	'admin/comments/$Action' => 'CommentAdmin',
	'admin/ReportField/$Action/$ID/$Type/$OtherID' => 'ReportField_Controller',
	'admin/bulkload/$Action/$ID/$OtherID' => 'BulkLoaderAdmin',
	'admin/ImageEditor/$Action' => 'ImageEditor',
	'admin/$Action/$ID/$OtherID' => 'CMSMain',
));

// Built-in modules
LeftAndMain::populate_default_menu();

?>