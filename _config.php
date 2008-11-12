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

// Javascript combined files
Requirements::combine_files(
	'assets/base.js',
	array(
		'jsparty/prototype.js',
		'jsparty/behaviour.js',
		'jsparty/prototype_improvements.js',
		'jsparty/jquery/jquery.js',
		'jsparty/jquery/plugins/livequery/jquery.livequery.js',
		'jsparty/jquery/plugins/effen/jquery.fn.js',
		'sapphire/javascript/core/jquery.ondemand.js',
		'jsparty/jquery/jquery_improvements.js',
		'jsparty/firebug/firebugx.js',
		'sapphire/javascript/i18n.js',
	)
);

Requirements::combine_files(
	'assets/leftandmain.js',
	array(
		'jsparty/loader.js',
		'jsparty/hover.js',
		'jsparty/layout_helpers.js',
		'jsparty/scriptaculous/effects.js',
		'jsparty/scriptaculous/dragdrop.js',
		'jsparty/scriptaculous/controls.js',
		'jsparty/greybox/AmiJS.js',
		'jsparty/greybox/greybox.js',
		'cms/javascript/LeftAndMain.js',
		'cms/javascript/LeftAndMain_left.js',
		'cms/javascript/LeftAndMain_right.js',
		//'jsparty/tiny_mce2/tiny_mce_src.js',
		'jsparty/tree/tree.js',
		'jsparty/tabstrip/tabstrip.js',
		'cms/javascript/TinyMCEImageEnhancement.js',
		'jsparty/SWFUpload/SWFUpload.js',
		'cms/javascript/Upload.js',
		'sapphire/javascript/TreeSelectorField.js',
 		'cms/javascript/ThumbnailStripField.js',
	)
);

Requirements::combine_files(
	'assets/cmsmain.js',
	array(
		'cms/javascript/CMSMain.js',
		'cms/javascript/CMSMain_left.js',
		'cms/javascript/CMSMain_right.js',
		'cms/javascript/SideTabs.js',
		'cms/javascript/TaskList.js',
		'cms/javascript/SideReports.js',
		'cms/javascript/LangSelector.js',
		'cms/javascript/TranslationTab.js',
		'jsparty/calendar/calendar.js',
		'jsparty/calendar/lang/calendar-en.js',
		'jsparty/calendar/calendar-setup.js',
	)
);

CMSMenu::add_link(
	'Help', 
	_t('LeftAndMain.HELP', 'Help', PR_HIGH, 'Menu title'), 
	'http://userhelp.silverstripe.com'
);

?>
