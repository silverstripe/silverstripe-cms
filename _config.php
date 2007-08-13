<?php

Director::addRules(50, array(
	'processes/$Action/$ID/$Batch' => 'BatchProcess_Controller',
	'silverstripe/' => '->admin/',
	'cms/' => '->admin/',
	'admin/security/$Action/$ID/$OtherID' => 'SecurityAdmin',
	'admin/help/$Action/$ID' => 'CMSHelp',
	'admin/newsletter/$Action/$ID' => 'NewsletterAdmin',
	'admin/reports/$Action/$ID' => 'ReportAdmin',
	'admin/assets/$Action/$ID' => 'AssetAdmin',
	'admin/comments/$Action' => 'CommentAdmin',
	'admin/ReportField/$Action/$ID/$Type/$OtherID' => 'ReportField_Controller',
	'admin/bulkload/$Action/$ID/$OtherID' => 'BulkLoaderAdmin',
	'admin/$Action/$ID/$OtherID' => 'CMSMain',
	'unsubscribe/$Email/$MailingList' => 'Unsubscribe_Controller',
	'membercontrolpanel/$Email' => 'MemberControlPanel'
));

?>
