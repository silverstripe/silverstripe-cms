<?php
/**
 * Extended URL rules for the CMS module
 * 
 * @package cms
 */
Director::addRules(50, array(
	'processes//$Action/$ID/$Batch' => 'BatchProcess_Controller',
	'silverstripe' => '->admin',
	'cms' => '->admin',
	'admin/help//$Action/$ID' => 'CMSHelp',
	'admin/ReportField//$Action/$ID/$Type/$OtherID' => 'ReportField_Controller',
	'admin/bulkload//$Action/$ID/$OtherID' => 'BulkLoaderAdmin',
	'admin//ImageEditor/$Action' => 'ImageEditor',
	'admin/cms//$Action/$ID/$OtherID' => 'CMSMain', 
	'PageComment//$Action/$ID' => 'PageComment_Controller',
	'dev/buildcache' => 'RebuildStaticCacheTask',
));

CMSMenu::populate_menu();

CMSMenu::add_link(
	'Help', 
	_t('LeftAndMain.HELP', 'Help', PR_HIGH, 'Menu title'), 
	'http://userhelp.silverstripe.com'
);

?>
