<?php

/**
 * Afrikaans (South Africa) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('af_ZA', $lang) && is_array($lang['af_ZA'])) {
	$lang['af_ZA'] = array_merge($lang['en_US'], $lang['af_ZA']);
} else {
	$lang['af_ZA'] = $lang['en_US'];
}

$lang['af_ZA']['AssetAdmin']['CHOOSEFILE'] = 'Kies lêer:';
$lang['af_ZA']['AssetAdmin']['FILESREADY'] = 'Lêers klaar opgelaai:';
$lang['af_ZA']['AssetAdmin']['MENUTITLE'] = 'Lêers & Prente';
$lang['af_ZA']['AssetAdmin']['MENUTITLE'] = 'Lêers & Prente';
$lang['af_ZA']['AssetAdmin']['NOTHINGTOUPLOAD'] = 'Daar was niks om op te laai nie';
$lang['af_ZA']['AssetAdmin']['THUMBSDELETED'] = '%s ongebruikte miniatuur prentjies was verwyder';
$lang['af_ZA']['AssetAdmin']['UPLOAD'] = 'Laai Lêers Hier Onder Gelys Op';
$lang['af_ZA']['AssetAdmin']['UPLOADEDX'] = 'Opgelaaide %s lêers';
$lang['af_ZA']['AssetAdmin_left.ss']['ENABLEDRAGGING'] = 'Laat sleep-en-los orden toe';
$lang['af_ZA']['AssetTableField']['IMAGE'] = 'Prent';
$lang['af_ZA']['AssetTableField']['MAIN'] = 'Hoof';
$lang['af_ZA']['AssetTableField']['URL'] = 'URL';
$lang['af_ZA']['CMSMain']['ACCESS'] = 'Toegang tot \'%s\' afdeling';
$lang['af_ZA']['CMSMain']['CANCEL'] = 'Kanselleer';
$lang['af_ZA']['CMSMain']['CHOOSEREPORT'] = '(Kies \'n verslag)';
$lang['af_ZA']['CMSMain']['EMAIL'] = 'Epos';
$lang['af_ZA']['CMSMain']['MENUTITLE'] = 'Werf Inhoud';
$lang['af_ZA']['CMSMain']['MENUTITLE'] = 'Blaaie';
$lang['af_ZA']['CMSMain']['NEW'] = 'Nuut';
$lang['af_ZA']['CMSMain']['OK'] = 'OK';
$lang['af_ZA']['CMSMain']['PAGENOTEXISTS'] = 'Hierdie bladsy bestaan nie';
$lang['af_ZA']['CMSMain']['PRINT'] = 'Druk';
$lang['af_ZA']['CMSMain']['PUBALLCONFIRM'] = 'Publiseer asseblief elke bladsy in die werf en kopieër inhoud fase na lewendig';
$lang['af_ZA']['CMSMain']['REMOVED'] = 'Het \'%s\'%s verwyder van lewendige werf';
$lang['af_ZA']['CMSMain']['REMOVEDPAGE'] = '\'%s\' verwyder uit die gepubliseerde werf';
$lang['af_ZA']['CMSMain']['REPORT'] = 'Verslag';
$lang['af_ZA']['CMSMain']['RESTORED'] = 'Herstel \'%s\' suksesvol';
$lang['af_ZA']['CMSMain']['ROLLBACK'] = 'Rol terug na hierdie weergawe';
$lang['af_ZA']['CMSMain']['ROLLEDBACKPUB'] = 'Terug na gepubliseerde weergawe gerol. Nuwe weergawe nommer is #%d';
$lang['af_ZA']['CMSMain']['ROLLEDBACKVERSION'] = 'Terug na weergawe #%d gerol. Nuwe weergawe is #%d';
$lang['af_ZA']['CMSMain']['VERSIONSNOPAGE'] = 'Kan nie bladsy #%d vind nie';
$lang['af_ZA']['CMSMain']['VIEWING'] = 'U kyk weergawe #%s, geskep %s deur %s';
$lang['af_ZA']['CMSMain_left.ss']['BATCHACTIONS'] = 'Bondel Aksies';
$lang['af_ZA']['CMSMain_left.ss']['CREATE'] = 'Skep';
$lang['af_ZA']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'Laat sleep-en-los orden toe';
$lang['af_ZA']['CMSMain_left.ss']['SEARCH'] = 'Soek';
$lang['af_ZA']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Blad Vertakking';
$lang['af_ZA']['CMSMain_right.ss']['CHOOSEPAGE'] = 'Kies asseblief \'n bladsy van die linkerkant.';
$lang['af_ZA']['CMSMain_right.ss']['WELCOMETO'] = 'Welkom by';
$lang['af_ZA']['CommentAdmin']['ACCEPT'] = 'Aanvaar';
$lang['af_ZA']['CommentAdmin']['APPROVED'] = 'Het %s aanmerkings aanvaar';
$lang['af_ZA']['CommentAdmin']['APPROVEDCOMMENTS'] = 'Aanvaarde Aanmerkings';
$lang['af_ZA']['CommentAdmin']['AUTHOR'] = 'Outeur';
$lang['af_ZA']['CommentAdmin']['COMMENT'] = 'Aanmerking';
$lang['af_ZA']['CommentAdmin']['COMMENTS'] = 'Aanmerkings';
$lang['af_ZA']['CommentAdmin']['DATEPOSTED'] = 'Datum Gepos';
$lang['af_ZA']['CommentAdmin']['DELETE'] = 'Verwyder';
$lang['af_ZA']['CommentAdmin']['DELETEALL'] = 'Verwyder Almal';
$lang['af_ZA']['CommentAdmin']['DELETED'] = 'Het %s aanmerkings verwyder';
$lang['af_ZA']['CommentAdmin']['MENUTITLE'] = 'Opmerkings';
$lang['af_ZA']['CommentAdmin']['MENUTITLE'] = 'Opmerkings';
$lang['af_ZA']['CommentAdmin']['NAME'] = 'Naam';
$lang['af_ZA']['CommentAdmin']['PAGE'] = 'Bladsy';
$lang['af_ZA']['CommentTableField']['SEARCH'] = 'Soek';
$lang['af_ZA']['LeftAndMain']['HELP'] = 'Help';
$lang['af_ZA']['LeftAndMain']['SITECONTENTLEFT'] = 'Werf Inhoud';
$lang['af_ZA']['LeftAndMain.ss']['APPVERSIONTEXT1'] = 'Hierdie is die';
$lang['af_ZA']['LeftAndMain.ss']['APPVERSIONTEXT2'] = 'weergawe wat u huidiglik hardloop, tegnies is dit die CVS tak';
$lang['af_ZA']['LeftAndMain.ss']['EDIT'] = 'Verander';
$lang['af_ZA']['LeftAndMain.ss']['LOADING'] = 'Laai...';
$lang['af_ZA']['LeftAndMain.ss']['LOGGEDINAS'] = 'Ingeteken as';
$lang['af_ZA']['LeftAndMain.ss']['LOGOUT'] = 'Teken Uit';
$lang['af_ZA']['LeftAndMain.ss']['PUBLIS'] = 'Publiseer Werf';
$lang['af_ZA']['LeftAndMain.ss']['REQUIREJS'] = 'Die IBS vereis dat u JavaScript in staat stel.';
$lang['af_ZA']['LeftAndMain.ss']['SSWEB'] = 'Silverstripe Webwerf';
$lang['af_ZA']['LeftAndMain']['STATUSPUBLISHEDSUCCESS'] = '\'%s\' Suksesvol Gepubliseer';
$lang['af_ZA']['MemberImportForm']['ResultCreated'] = 'Het %d lede geskep';
$lang['af_ZA']['MemberImportForm']['ResultDeleted'] = 'Het %d lede verwyder';
$lang['af_ZA']['MemberImportForm']['ResultNone'] = 'Geen veranderinge';
$lang['af_ZA']['MemberImportForm']['ResultUpdated'] = 'Het %d lede opgedateer';
$lang['af_ZA']['MemberList.ss']['FILTER'] = 'Filtreer';
$lang['af_ZA']['MemberTableField']['DeleteTitleText'] = 'Verwyder van hierdie groep';
$lang['af_ZA']['MemberTableField']['DeleteTitleTextDatabase'] = 'Verwyder van databasis en alle groepe';
$lang['af_ZA']['MemberTableField.ss']['ADDNEW'] = 'Byvoeg nuwe';
$lang['af_ZA']['ModelAdmin']['CREATEBUTTON'] = 'Byvoeg \'%s\'';
$lang['af_ZA']['PageComment']['COMMENTBY'] = 'Opmerk deur \'%s\' op %s';
$lang['af_ZA']['PageComment']['PLURALNAME'] = 'Bladsy Opmerkings';
$lang['af_ZA']['PageComment']['SINGULARNAME'] = 'Bladsy Opmerking';
$lang['af_ZA']['PageCommentInterface']['COMMENTERURL'] = 'U werbwerf URL';
$lang['af_ZA']['PageCommentInterface.ss']['RSSFEEDALLCOMMENTS'] = 'RSS voer vir alle aanmerkings';
$lang['af_ZA']['PageCommentInterface.ss']['RSSVIEWALLCOMMENTS'] = 'Wys alle Aanmerkings';
$lang['af_ZA']['Permission']['CMS_ACCESS_CATEGORY'] = 'IBS Toegang';
$lang['af_ZA']['Permissions']['PERMISSIONS_CATEGORY'] = 'Rolle en toegang permissies';
$lang['af_ZA']['ReportAdmin']['MENUTITLE'] = 'Verslae';
$lang['af_ZA']['ReportAdmin_right.ss']['WELCOME1'] = 'Welcome by die';
$lang['af_ZA']['ReportAdmin_right.ss']['WELCOME2'] = 'rapporteering afdeling. Kies asseblief \'n verslag van links.';
$lang['af_ZA']['SecurityAdmin']['ACCESS_HELP'] = 'Laat toe wys, byvoeging en verandering van gebruikers, so wel as die toekenning van permissies en rolle aan hulle.';
$lang['af_ZA']['SecurityAdmin']['APPLY_ROLES'] = 'Wend rolle tot groepe toe';
$lang['af_ZA']['SecurityAdmin']['APPLY_ROLES_HELP'] = 'Vermoë om rolle toegeken aan \'n groep te verander. Benodig die "Toegang tot \'Sekuriteit\' afdeling\' permissie.';
$lang['af_ZA']['SecurityAdmin']['EDITPERMISSIONS_HELP'] = 'Vermoë om Permissies en IP Adresse vir \'n groep te verander. Benodig die "Toegang tot \'Sekuriteit\' afdeling" permissie.';
$lang['af_ZA']['SecurityAdmin']['MemberListCaution'] = 'Waarskuwing: Deur lede te verwyder van hierdie lys sal hulle ook van alle groepe en die databasis verwyder';
$lang['af_ZA']['SecurityAdmin']['MENUTITLE'] = 'Sekuriteit';
$lang['af_ZA']['SecurityAdmin']['MENUTITLE'] = 'Sekuriteit';
$lang['af_ZA']['SecurityAdmin']['TABIMPORT'] = 'Voer In';
$lang['af_ZA']['SecurityAdmin']['TABROLES'] = 'Rolle';
$lang['af_ZA']['SecurityAdmin_left.ss']['ENABLEDRAGGING'] = 'Laat sleep-en-los orden toe';
$lang['af_ZA']['SecurityAdmin_MemberImportForm']['BtnImport'] = 'Voer In';
$lang['af_ZA']['SecurityAdmin_MemberImportForm']['FileFieldLabel'] = 'CSV Lêer <small>(Laat toe uitbreidings: *.csv)</small>';
$lang['af_ZA']['SecurityAdmin_right.ss']['WELCOME1'] = 'Welcome by die';
$lang['af_ZA']['SecurityAdmin_right.ss']['WELCOME2'] = 'Sekuriteits administrasie afdeling. Kies asseblief a groep van links.';
$lang['af_ZA']['SideReport']['BROKENFILES'] = 'Bladsye met gebreekte lêers';
$lang['af_ZA']['SideReport']['BROKENLINKS'] = 'Bladsye met gebreekte skakels';
$lang['af_ZA']['SideReport']['BrokenLinksGroupTitle'] = 'Gebreekte skakels verslae';
$lang['af_ZA']['SideReport']['ContentGroupTitle'] = 'Inhoud verslae';
$lang['af_ZA']['SideReport']['OtherGroupTitle'] = 'Ander';
$lang['af_ZA']['SideReport']['ParameterLiveCheckbox'] = 'Kies lewendige werf';
$lang['af_ZA']['SideReport']['REPEMPTY'] = 'Die %s verslag is leeg.';
$lang['af_ZA']['TableListField.ss']['NOITEMSFOUND'] = 'Geen items gevind';
$lang['af_ZA']['TableListField.ss']['SORTASC'] = 'Sorteer in stygende orde';
$lang['af_ZA']['TableListField.ss']['SORTDESC'] = 'Sorteer in dalende orde';
$lang['af_ZA']['ViewArchivedEmail.ss']['HAVEASKED'] = 'U het gevra om die inhoud van ons werf te sien op';

?>