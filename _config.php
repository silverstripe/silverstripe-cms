<?php

/**
 * Extended URL rules for the CMS module
 * 
 * @package cms
 */
Director::addRules(50, array(
	'' => 'RootURLController',
	'admin/bulkload//$Action/$ID/$OtherID' => 'BulkLoaderAdmin',
	'admin/cms//$Action/$ID/$OtherID' => 'CMSMain', 
	'dev/buildcache/$Action' => 'RebuildStaticCacheTask',
));

Director::addRules(1, array(
	'$URLSegment//$Action/$ID/$OtherID' => 'ModelAsController',
));

// Register default side reports
SS_Report::register("SideReport", "SideReport_EmptyPages");
SS_Report::register("SideReport", "SideReport_RecentlyEdited");
SS_Report::register("SideReport", "SideReport_ToDo");
if (class_exists('SubsiteReportWrapper')) SS_Report::register('ReportAdmin', 'SubsiteReportWrapper("BrokenLinksReport")',-20);
else SS_Report::register('ReportAdmin', 'BrokenLinksReport',-20);


/**
 * Register the default internal shortcodes.
 */
ShortcodeParser::get('default')->register('sitetree_link', array('SiteTree', 'link_shortcode_handler'));

Object::add_extension('File', 'SiteTreeFileDecorator');