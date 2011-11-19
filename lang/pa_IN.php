<?php

/**
 * Punjabi (India) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('pa_IN', $lang) && is_array($lang['pa_IN'])) {
	$lang['pa_IN'] = array_merge($lang['en_US'], $lang['pa_IN']);
} else {
	$lang['pa_IN'] = $lang['en_US'];
}

$lang['pa_IN']['AssetAdmin']['CHOOSEFILE'] = 'ਫਾਈਲ ਚੁਣੋੋੋੋ';
$lang['pa_IN']['AssetAdmin']['DELETEDX'] = '%s ਫਾਈਲਾਂ ਕੱਟੀਆ. %s';
$lang['pa_IN']['AssetAdmin']['FILESREADY'] = 'ਫਾਈਲਾਂ ਅੱਪਲੋੋਡਡ ਲਈ ਿਤਆਰ ਹਨ।';
$lang['pa_IN']['AssetAdmin']['MENUTITLE'] = 'ਫਾਈਲਾਂ ਅਤੇ ਤਸਵੀਰਾਂ';
$lang['pa_IN']['AssetAdmin']['MOVEDX'] = 'ਹਟਾਈਆ %s ਫਾਈਲ਼ਾ	ਂ
';
$lang['pa_IN']['AssetAdmin']['NEWFOLDER'] = 'ਨਵਾਂ ਫੋਲਡਰ';
$lang['pa_IN']['AssetAdmin']['NOTHINGTOUPLOAD'] = 'There was nothing to upload';
$lang['pa_IN']['AssetAdmin']['NOWBROKEN'] = 'ਇਹਨਾ ਪੰਿਨਆਂ ਉੱਤੇ ਹੁਣ ਖ਼ਰਾਬ ਜੋੜ ਹਨ।';
$lang['pa_IN']['AssetAdmin']['SAVEDFILE'] = 'Saved file %s';
$lang['pa_IN']['AssetAdmin']['SAVEFOLDERNAME'] = 'Save folder name';
$lang['pa_IN']['AssetAdmin']['UPLOAD'] = 'ਹੇਠਾ ਦਰਜ ਅੱਪਲੋਡ ਕਰੋ';
$lang['pa_IN']['AssetAdmin']['UPLOADEDX'] = '%s ਫਾਈਲਾਂ ਅੱਪਲੋੋਡਡ';
$lang['pa_IN']['AssetAdmin_left.ss']['CREATE'] = 'ਬਣਾਉ';
$lang['pa_IN']['AssetAdmin_left.ss']['DELETE'] = 'ਕੱਟੋਂ';
$lang['pa_IN']['AssetAdmin_left.ss']['DELFOLDERS'] = 'Delete the selected folders';
$lang['pa_IN']['AssetAdmin_left.ss']['FOLDERS'] = 'ਫੋਲਡਰਾਂ ';
$lang['pa_IN']['AssetAdmin_left.ss']['GO'] = 'ਜਾਓ';
$lang['pa_IN']['AssetAdmin_left.ss']['SELECTTODEL'] = 'Select the folders that you want to delete and then click the button below';
$lang['pa_IN']['AssetAdmin_left.ss']['TOREORG'] = 'To reorganise your folders, drag them around as desired.';
$lang['pa_IN']['AssetAdmin_right.ss']['CHOOSEPAGE'] = 'ਿਕ੍ਰਪਾ ਕਰਕੇ ਖੱਬੇਓ ਇੱਕ ਪੰਨਾ ਚੁਣੋ  ';
$lang['pa_IN']['AssetAdmin_right.ss']['WELCOME'] = 'ਜੀ ਆਇਆਂ ਨੂੰ	';
$lang['pa_IN']['AssetAdmin_uploadiframe.ss']['PERMFAILED'] = 'You do not have permission to upload files into this folder.';
$lang['pa_IN']['AssetTableField']['CREATED'] = 'ਪਹਿਲਾਂ  ਅੱਪਲੋਡਡ';
$lang['pa_IN']['AssetTableField']['DIM'] = 'Dimensions';
$lang['pa_IN']['AssetTableField']['FILENAME'] = 'ਫਾਈਲ ਦਾ ਨਾਮ ';
$lang['pa_IN']['AssetTableField']['LASTEDIT'] = 'ਆਖਰੀ ਤਬਦੀਲੀ	';
$lang['pa_IN']['AssetTableField']['NOLINKS'] = 'This file hasn\'t been linked to from any pages.';
$lang['pa_IN']['AssetTableField']['OWNER'] = 'ਮਾਲਕ';
$lang['pa_IN']['AssetTableField']['PAGESLINKING'] = 'The following pages link to this file:';
$lang['pa_IN']['AssetTableField']['SIZE'] = 'ਸਾਈਜ਼';
$lang['pa_IN']['AssetTableField.ss']['DELFILE'] = 'ਚੁਣੀ ਹੋਈ ਫਾਈਲ ਕੱਟੋਂ';
$lang['pa_IN']['AssetTableField.ss']['DRAGTOFOLDER'] = 'Drag to folder on left to move file';
$lang['pa_IN']['AssetTableField']['TITLE'] = 'ਟਾਈਟਲ';
$lang['pa_IN']['AssetTableField']['TYPE'] = 'ਿਕਸਮ';
$lang['pa_IN']['CMSMain']['CANCEL'] = 'ਰੱਦ	';
$lang['pa_IN']['CMSMain']['CHOOSEREPORT'] = '(ਇਕ ਰੀਪੋਰਟ ਚੁਣ ੋ)
';
$lang['pa_IN']['CMSMain']['COMPARINGV'] = 'You are comparing versions #%d and #%d';
$lang['pa_IN']['CMSMain']['COPYPUBTOSTAGE'] = 'Do you really want to copy the published content to the stage site?';
$lang['pa_IN']['CMSMain']['EMAIL'] = 'ਈਮੇਲ';
$lang['pa_IN']['CMSMain']['GO'] = 'ਜਾਓ ';
$lang['pa_IN']['CMSMain']['MENUTITLE'] = 'ਸਾਈਟ ਸਮਾਨ';
$lang['pa_IN']['CMSMain']['METADESCOPT'] = 'ਵਰਣਨ	';
$lang['pa_IN']['CMSMain']['METAKEYWORDSOPT'] = 'Keywords';
$lang['pa_IN']['CMSMain']['NEW'] = 'ਨਵਾਂ';
$lang['pa_IN']['CMSMain']['NOCONTENT'] = 'ਕੋਈ ਸਮਾਨ ਨਹੀ ਹੈ';
$lang['pa_IN']['CMSMain']['OK'] = 'ਠੀਕ ';
$lang['pa_IN']['CMSMain']['PAGENOTEXISTS'] = 'ਇਹ ਪੰਨਾ ਨਹੀ ਹੈ';
$lang['pa_IN']['CMSMain']['PRINT'] = 'ਛਾਪੋ';
$lang['pa_IN']['CMSMain']['PUBALLCONFIRM'] = 'ਿਕ੍ਰਪਾ ਕਰਕੇ ਵੈੱਬ-ਸਾਈਟ ਦਾ ਹਰੇਕ ਪੰਨਾ ਛਾਪੋ, ਸਮਾਨ ਨੰੂ  ਿਜ਼ੰਓੁਦਾ ਕਰ ਰਹੇ ਹਾ
';
$lang['pa_IN']['CMSMain']['PUBALLFUN'] = '" ਸਾਰ ਛਾਪੋ " ਕਾਰਜਕਰਨੀਂ';
$lang['pa_IN']['CMSMain']['PUBALLFUN2'] = 'Pressing this button will do the equivalent of going to every page and pressing "publish". It\'s
intended to be used after there have been massive edits of the content, such as when the site was
first built.';
$lang['pa_IN']['CMSMain']['PUBPAGES'] = 'ਕੰਮ ਕੀਤਾ : ਛਾਪੋ %d ਪੰਨੇ ';
$lang['pa_IN']['CMSMain']['REMOVEDFD'] = 'ਕੱਚੀ ਸਾਈਟ ਤੋਂ ਉਤਾਿਰਆ ';
$lang['pa_IN']['CMSMain']['REMOVEDPAGE'] = '\'%s\' ਨੰੂ ਛਾਪੀ ਹੋਈ ਸਾਈਟ ਤੋਂ ਉਤਾਿਰਆ';
$lang['pa_IN']['CMSMain']['RESTORED'] = 'ਬਹਾਲੀ \'%s\' ਕਾਮਯਾਬ।';
$lang['pa_IN']['CMSMain']['ROLLBACK'] = 'Roll back to this version';
$lang['pa_IN']['CMSMain']['ROLLEDBACKPUB'] = 'Rolled back to published version. New version number is #%d';
$lang['pa_IN']['CMSMain']['ROLLEDBACKVERSION'] = 'Rolled back to version #%d. New version number is #%d';
$lang['pa_IN']['CMSMain']['SAVE'] = 'ਬਚਾ';
$lang['pa_IN']['CMSMain']['STATUSOPT'] = 'ਦਰਜਾ	';
$lang['pa_IN']['CMSMain']['TOTALPAGES'] = 'ਸਾਰੇ ਪੰਨੇ ';
$lang['pa_IN']['CMSMain']['VERSIONSNOPAGE'] = 'ਪੰਨਾ ਨਹੀ ਲੱਿਭਆ #%d';
$lang['pa_IN']['CMSMain']['VIEWING'] = 'You are viewing version #%d, created %s';
$lang['pa_IN']['CMSMain_left.ss']['ADDEDNOTPUB'] = 'ਕੱਚੀ ਸਾਈਟ ਨਾਲ ਜੋੜਿਆ ਅਤੇ ਅਜੇ ਛਾਿਪਆ ਨਹੀ
';
$lang['pa_IN']['CMSMain_left.ss']['ADDSEARCHCRITERIA'] = 'ਕਸੌਟੀ ਜੋੜੋ ';
$lang['pa_IN']['CMSMain_left.ss']['BATCHACTIONS'] = 'ਗਰੁੱਪ ਅਮਲਾਂ';
$lang['pa_IN']['CMSMain_left.ss']['CHANGED'] = 'ਬਦਿਲਆ';
$lang['pa_IN']['CMSMain_left.ss']['CLOSEBOX'] = 'ਇਸ ਡੱਬੀ ਨੰੂ ਬੰਦ ਕਰਨ ਲਈ ਕਿਲੱਕ  ਕਰੋ';
$lang['pa_IN']['CMSMain_left.ss']['COMPAREMODE'] = 'ਤੁਲਨਾ ਕਰਨਾ ਮੂਡ ( ਹੇਠਾਂ 2 ਕਿਲੱਕ ਕਰੋ )  
';
$lang['pa_IN']['CMSMain_left.ss']['CREATE'] = 'ਸਾਜਣਾ';
$lang['pa_IN']['CMSMain_left.ss']['DEL'] = 'ਕੱਿਟਆ';
$lang['pa_IN']['CMSMain_left.ss']['DELETECONFIRM'] = 'ਚੁਣੇ ਹਏ ਪੰਿਨਆ ਨੰੂ ਕੱਟੋਂ';
$lang['pa_IN']['CMSMain_left.ss']['DELETEDSTILLLIVE'] = 'ਕੱਚੀ ਸਾਈਟ ਤੋਂ ਉਤਾਿਰਆ ਪਰ ਅਜੇ ਵੀ ਛਾਿਪਆ ਹੈ।';
$lang['pa_IN']['CMSMain_left.ss']['EDITEDNOTPUB'] = 'ਕੱਚੀ ਸਾਈਟ ਉੱਤੇ ਐਿਡਟ ਕੀਤਾ ਅਤੇ ਛਾਿਪਆ ਨਹੀ';
$lang['pa_IN']['CMSMain_left.ss']['EDITEDSINCE'] = 'Edited Since';
$lang['pa_IN']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'ਘੜੀਸਣ ਦੀ ਇਜਾਜ਼ਤ ਅਤ ੇamp; ਿਡਗਣਾ ਰੀਆਰਡਰ ਕਰਨ ਦੀ ਇਜਾਜ਼ਤ 

';
$lang['pa_IN']['CMSMain_left.ss']['GO'] = 'ਜਾਓ ';
$lang['pa_IN']['CMSMain_left.ss']['KEY'] = 'ਚਾਬੀ:';
$lang['pa_IN']['CMSMain_left.ss']['NEW'] = 'ਨਵਾਂ';
$lang['pa_IN']['CMSMain_left.ss']['OPENBOX'] = 'ਇਸ ਡੱਬੀ ਨੰੂ ਖੋਲਣ ਲਈ ਕਿਲੱਕ  ਕਰੋ';
$lang['pa_IN']['CMSMain_left.ss']['PAGEVERSIONH'] = 'ਪੰਨਾ ਵਰਸ਼ਨ ਇਤਿਹਾਸ 
';
$lang['pa_IN']['CMSMain_left.ss']['PUBLISHCONFIRM'] = 'Publish the selected pages';
$lang['pa_IN']['CMSMain_left.ss']['SEARCH'] = 'ਖੋਜ';
$lang['pa_IN']['CMSMain_left.ss']['SEARCHTITLE'] = 'Search through URL, ਟਾਈਟਲ, Menu ਟਾਈਟਲ, &amp; ਸਮਾਨ';
$lang['pa_IN']['CMSMain_left.ss']['SELECTPAGESACTIONS'] = 'Select the pages that you want to change &amp; then click an action:';
$lang['pa_IN']['CMSMain_left.ss']['SHOWONLYCHANGED'] = 'Show only changed pages';
$lang['pa_IN']['CMSMain_left.ss']['SHOWUNPUB'] = 'ਅਣਛਾਪੇ ਪੰਨੇ ਿਦਖਾਓ';
$lang['pa_IN']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'ਸਾਈਟ ਸਮਾਨ  ਬਣਾਵਟ';
$lang['pa_IN']['CMSMain_left.ss']['SITEREPORTS'] = 'ਵੈੱਬ-ਸਾਈਟ ਰੀਪੋਰਟਾਂ';
$lang['pa_IN']['CMSMain_right.ss']['CHOOSEPAGE'] = 'ਿਕ੍ਰਪਾ ਕਰਕੇ ਖੱਬੇਓ ਇੱਕ ਪੰਨਾ ਚੁਣੋ  ';
$lang['pa_IN']['CMSMain_right.ss']['WELCOMETO'] = 'ਜੀ ਆਇਆਂ ਨੂੰ	';
$lang['pa_IN']['CMSMain_versions.ss']['AUTHOR'] = 'ਲੇਖਕ	';
$lang['pa_IN']['CMSMain_versions.ss']['NOTPUB'] = 'ਅਣਛਾਪੀ';
$lang['pa_IN']['CMSMain_versions.ss']['PUBR'] = 'ਪ੍ਰਕਾਸ਼ਕ	';
$lang['pa_IN']['CMSMain_versions.ss']['UNKNOWN'] = 'Unknown';
$lang['pa_IN']['CMSMain_versions.ss']['WHEN'] = 'ਕਦੋਂ	';
$lang['pa_IN']['CommentAdmin']['MENUTITLE'] = 'ਿਟੱਪਣੀਆਂ';
$lang['pa_IN']['ImageEditor.ss']['CANCEL'] = 'ਰੱਦ	';
$lang['pa_IN']['ImageEditor.ss']['CROP'] = 'crop';
$lang['pa_IN']['ImageEditor.ss']['HEIGHT'] = 'ਲੰਬਾਈ';
$lang['pa_IN']['ImageEditor.ss']['REDO'] = 'redo';
$lang['pa_IN']['ImageEditor.ss']['ROT'] = 'rotate';
$lang['pa_IN']['ImageEditor.ss']['SAVE'] = 'save&nbsp;image';
$lang['pa_IN']['ImageEditor.ss']['UNDO'] = 'undo';
$lang['pa_IN']['ImageEditor.ss']['UNTITLED'] = 'Untitled Document';
$lang['pa_IN']['ImageEditor.ss']['WIDTH'] = 'ਚੌੜਾਈ';
$lang['pa_IN']['LeftAndMain']['CHANGEDURL'] = 'Changed URL to \'%s\'';
$lang['pa_IN']['LeftAndMain']['HELP'] = 'ਮੱਦਦ ';
$lang['pa_IN']['LeftAndMain']['PAGETYPE'] = 'ਪੰਨੇ ਦੀ ਿਕਸਮ';
$lang['pa_IN']['LeftAndMain']['PERMAGAIN'] = 'You have been logged out of the CMS. If you would like to log in again, enter a username and password below.';
$lang['pa_IN']['LeftAndMain']['PERMALREADY'] = 'I\'m sorry, but you can\'t access that part of the CMS. If you want to log in as someone else, do so below';
$lang['pa_IN']['LeftAndMain']['PERMDEFAULT'] = 'Please choose an authentication method and enter your credentials to access the CMS.';
$lang['pa_IN']['LeftAndMain']['PLEASESAVE'] = 'Please Save Page: This page could not be upated because it hasn\'t been saved';
$lang['pa_IN']['LeftAndMain']['REQUESTERROR'] = 'Error in request';
$lang['pa_IN']['LeftAndMain']['SAVED'] = 'ਬਚਾਿੲਆ';
$lang['pa_IN']['LeftAndMain']['SAVEDUP'] = 'Saved';
$lang['pa_IN']['LeftAndMain']['SITECONTENTLEFT'] = 'ਸਾਈਟ ਸਮਾਨ';
$lang['pa_IN']['LeftAndMain.ss']['APPVERSIONTEXT1'] = 'ਇਹ ਹੈ ';
$lang['pa_IN']['LeftAndMain.ss']['APPVERSIONTEXT2'] = 'version that you are currently running, technically it\'s the CVS branch';
$lang['pa_IN']['LeftAndMain.ss']['ARCHS'] = 'ਪੁਰਾਣੀ ਸਾਈਟ  ';
$lang['pa_IN']['LeftAndMain.ss']['DRAFTS'] = 'ਕੱਚੀ ਸਾਈਟ ';
$lang['pa_IN']['LeftAndMain.ss']['EDIT'] = 'ਇਡਟ';
$lang['pa_IN']['LeftAndMain.ss']['EDITPROFILE'] = 'Profile';
$lang['pa_IN']['LeftAndMain.ss']['LOADING'] = 'ਲੱਦ ';
$lang['pa_IN']['LeftAndMain.ss']['LOGGEDINAS'] = 'Logged in as';
$lang['pa_IN']['LeftAndMain.ss']['LOGOUT'] = 'ਲਾਗ ਆਉਟ';
$lang['pa_IN']['LeftAndMain.ss']['PUBLIS'] = 'ਛਪੀ  ਸਾਈਟ ';
$lang['pa_IN']['LeftAndMain.ss']['SSWEB'] = 'ਸਿਲਵਰ-ਸਟਰਾਈਪ  ਵੈੱਬ-ਸਾਈਟ ';
$lang['pa_IN']['LeftAndMain.ss']['VIEWPAGEIN'] = 'Page view:';
$lang['pa_IN']['LeftAndMain']['STATUSTO'] = 'ਦਰਜਾ ਬਦਿਲਆ \'%s\'';
$lang['pa_IN']['MemberList.ss']['FILTER'] = 'ਿਫ਼ਲਟਰ';
$lang['pa_IN']['MemberList_Table.ss']['EMAIL'] = 'ਈਮੇਲ ਪਤਾ';
$lang['pa_IN']['MemberList_Table.ss']['FN'] = 'ਪਹਿਲਾ ਨਾਮ';
$lang['pa_IN']['MemberList_Table.ss']['PASSWD'] = 'Password';
$lang['pa_IN']['MemberList_Table.ss']['SN'] = 'ਗੋਤ';
$lang['pa_IN']['MemberTableField']['ADD'] = 'ਜੋੜ ੋ ';
$lang['pa_IN']['MemberTableField']['ADDEDTOGROUP'] = 'ਮੈਂਬਰ ਗਰੁੱਪ ਿਵੱਚ ਜੋਿੜਆ';
$lang['pa_IN']['MemberTableField.ss']['ADDNEW'] = 'ਨਵਾਂ ਜੋੜ ੋ 
';
$lang['pa_IN']['PageComment']['COMMENTBY'] = 'ਿਟੱਪਣੀ \'%s\' ਵੱਲੋਂ %s ਨੂੰ';
$lang['pa_IN']['PageCommentInterface.ss']['COMMENTS'] = 'ਿਟੱਪਣੀਆਂ';
$lang['pa_IN']['PageCommentInterface.ss']['NEXT'] = 'ਅਗਲਾ';
$lang['pa_IN']['PageCommentInterface.ss']['NOCOMMENTSYET'] = 'No one has commented on this page yet.';
$lang['pa_IN']['PageCommentInterface.ss']['POSTCOM'] = 'Post your comment';
$lang['pa_IN']['PageCommentInterface.ss']['PREV'] = 'ਪਿਛਲਾ';
$lang['pa_IN']['PageCommentInterface_singlecomment.ss']['ISNTSPAM'] = 'ਇਹ ਿਟੱਪਣੀ spam ਨਹੀ ਹੈ ';
$lang['pa_IN']['PageCommentInterface_singlecomment.ss']['ISSPAM'] = 'this comment is spam';
$lang['pa_IN']['PageCommentInterface_singlecomment.ss']['PBY'] = 'Posted by';
$lang['pa_IN']['PageCommentInterface_singlecomment.ss']['REMCOM'] = 'remove this comment';
$lang['pa_IN']['ReportAdmin_left.ss']['REPORTS'] = 'ਰੀਪੋਰਟਾਂ ';
$lang['pa_IN']['ReportAdmin_right.ss']['WELCOME1'] = 'ਜੀ ਆਇਆਂ ਨੂੰ';
$lang['pa_IN']['ReportAdmin_right.ss']['WELCOME2'] = 'ਰੀਪੋਰਟਾਂ ਦਾ ਭਾਗ।  ਿਕ੍ਰਪਾ ਕਰਕੇ ਖੱਬੇਉ ਇਕ ਰੀਪੋਰਟ ਚੁਣੋ।
';
$lang['pa_IN']['ReportAdmin_SiteTree.ss']['REPORTS'] = 'ਰੀਪੋਰਟਾਂ ';
$lang['pa_IN']['SecurityAdmin']['ADDMEMBER'] = 'ਮੈਂਬਰ ਜੋੜੋ';
$lang['pa_IN']['SecurityAdmin']['MENUTITLE'] = 'ਸੁਰੱਿਖਆ';
$lang['pa_IN']['SecurityAdmin']['NEWGROUP'] = 'ਨਵਾਂ ਗਰੁੱਪ	';
$lang['pa_IN']['SecurityAdmin']['SAVE'] = 'ਬਚਾਓ';
$lang['pa_IN']['SecurityAdmin']['SGROUPS'] = 'ਸੁਰੱਿਖਆ ਗਰੁੱਪ	';
$lang['pa_IN']['SecurityAdmin_left.ss']['CREATE'] = 'ਬਣਾਉ';
$lang['pa_IN']['SecurityAdmin_left.ss']['DEL'] = 'ਕੱਟੋਂ';
$lang['pa_IN']['SecurityAdmin_left.ss']['DELGROUPS'] = 'Delete the selected groups';
$lang['pa_IN']['SecurityAdmin_left.ss']['GO'] = 'ਜਾਓ ';
$lang['pa_IN']['SecurityAdmin_left.ss']['SECGROUPS'] = 'ਸੁਰੱਿਖਆ Groups';
$lang['pa_IN']['SecurityAdmin_left.ss']['SELECT'] = 'Select the pages that you want to delete and then click the button below';
$lang['pa_IN']['SecurityAdmin_left.ss']['TOREORG'] = 'To reorganise your site, drag the pages around as desired.';
$lang['pa_IN']['SecurityAdmin_right.ss']['WELCOME1'] = 'ਜੀ ਆਇਆਂ ਨੂੰ';
$lang['pa_IN']['SecurityAdmin_right.ss']['WELCOME2'] = 'ਸੁਰੱਿਖਆ ਬੰਦੋਬਸਤ ਭਾਗ।  ਿਕ੍ਰਪਾ ਕਰਕੇ ਖੱਬੇਉ ਇਕ ਗਰੁੱਪ ਚੁਣੋ।';
$lang['pa_IN']['SideReport']['EMPTYPAGES'] = 'ਖ਼ਾਲੀ ਪੰਨੇ';
$lang['pa_IN']['SideReport']['LAST2WEEKS'] = 'Pages edited in the last 2 weeks';
$lang['pa_IN']['SideReport']['REPEMPTY'] = ' %s ਰੀਪੋਰਟ ਖ਼ਾਲੀ ਹੈ।';
$lang['pa_IN']['StaticExporter']['BASEURL'] = 'Base URL';
$lang['pa_IN']['StaticExporter']['EXPORTTO'] = 'ਉਹ ਫੋਲਡਰ ਤੇ ਭੇਜੋ';
$lang['pa_IN']['StaticExporter']['FOLDEREXPORT'] = 'Folder to export to';
$lang['pa_IN']['StaticExporter']['NAME'] = 'Static exporter';
$lang['pa_IN']['ThumbnailStripField.ss']['CHOOSEFOLDER'] = '(Choose a folder above)';
$lang['pa_IN']['ViewArchivedEmail.ss']['CANACCESS'] = 'You can access the archived site at this link:';
$lang['pa_IN']['ViewArchivedEmail.ss']['HAVEASKED'] = 'ਤੁਸੀ ਸਾਡੀ ਵੈੱਬ-ਸਾਈਟ ਦਾ ਸਮਾਨ ਦੇਖਣ ਲਈ ਿਕਹਾ ਸੀ। ਤਾਰੀਖ਼ ਸੀ ';

?>