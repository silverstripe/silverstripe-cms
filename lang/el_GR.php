<?php

/**
 * Greek (Greece) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('el_GR', $lang) && is_array($lang['el_GR'])) {
	$lang['el_GR'] = array_merge($lang['en_US'], $lang['el_GR']);
} else {
	$lang['el_GR'] = $lang['en_US'];
}

$lang['el_GR']['AssetAdmin']['CHOOSEFILE'] = 'Επιλογή αρχείου';
$lang['el_GR']['AssetAdmin']['FILESREADY'] = 'Αρχεία έτοιμα για μεταφόρτωση:';
$lang['el_GR']['AssetAdmin']['MENUTITLE'] = 'Αρχεία & Εικόνες';
$lang['el_GR']['AssetAdmin']['NOTHINGTOUPLOAD'] = 'Δεν υπάρχει κάτι για μεταφόρτωση';
$lang['el_GR']['AssetAdmin']['UPLOAD'] = 'Μεταφόρτωση Αρχείων της παρακάτω λίστας';
$lang['el_GR']['AssetAdmin']['UPLOADEDX'] = 'Μεταφορτώθηκαν %s αρχεία';
$lang['el_GR']['CMSMain']['CANCEL'] = 'Ακύρωση';
$lang['el_GR']['CMSMain']['CHOOSEREPORT'] = '(Επιλογή αναφοράς)';
$lang['el_GR']['CMSMain']['EMAIL'] = 'Email';
$lang['el_GR']['CMSMain']['MENUTITLE'] = 'Περιεχόμενο';
$lang['el_GR']['CMSMain']['NEW'] = 'Νέα';
$lang['el_GR']['CMSMain']['OK'] = 'Εντάξει';
$lang['el_GR']['CMSMain']['PRINT'] = 'Εκτύπωση';
$lang['el_GR']['CMSMain']['SAVE'] = 'Αποθήκευση';
$lang['el_GR']['CMSMain']['TOTALPAGES'] = 'Σύνολο σελίδων:';
$lang['el_GR']['CMSMain_left.ss']['BATCHACTIONS'] = 'Μαζικές Ενέργειες';
$lang['el_GR']['CMSMain_left.ss']['CHANGED'] = 'αλλαγμένο';
$lang['el_GR']['CMSMain_left.ss']['CLOSEBOX'] = 'Πατήστε για να κλείσει';
$lang['el_GR']['CMSMain_left.ss']['CREATE'] = 'Δημιουργία';
$lang['el_GR']['CMSMain_left.ss']['DEL'] = 'διεγραμμένο';
$lang['el_GR']['CMSMain_left.ss']['DELETECONFIRM'] = 'Διαγραφή επιλεγμένων σελίδων';
$lang['el_GR']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'Επιτρέψτε την drag &amp; drop ταξινόμηση';
$lang['el_GR']['CMSMain_left.ss']['KEY'] = 'Κλειδί:';
$lang['el_GR']['CMSMain_left.ss']['NEW'] = 'νέο';
$lang['el_GR']['CMSMain_left.ss']['OPENBOX'] = 'Πατήστε για να ανοίξει';
$lang['el_GR']['CMSMain_left.ss']['PAGEVERSIONH'] = 'Ιστορικό Έκδοσης Σελίδας';
$lang['el_GR']['CMSMain_left.ss']['SEARCH'] = 'Αναζήτηση';
$lang['el_GR']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Περιεχόμενο και Οργάνωση';
$lang['el_GR']['CMSMain_left.ss']['SITEREPORTS'] = 'Αναφορές Ιστού';
$lang['el_GR']['CMSMain_right.ss']['CHOOSEPAGE'] = 'Παρακαλώ επιλέξτε μια σελίδα απο τα αριστερά.';
$lang['el_GR']['CMSMain_right.ss']['WELCOMETO'] = 'Καλωσήλθατε στο';
$lang['el_GR']['CommentAdmin']['MENUTITLE'] = 'Σχόλια';
$lang['el_GR']['LeftAndMain']['HELP'] = 'Βοήθεια';
$lang['el_GR']['LeftAndMain']['REQUESTERROR'] = 'Λάθος αιτήματος';
$lang['el_GR']['LeftAndMain']['SAVED'] = 'αποθηκεύτηκαν';
$lang['el_GR']['LeftAndMain']['SAVEDUP'] = 'Αποθηκεύτηκαν';
$lang['el_GR']['LeftAndMain']['SITECONTENTLEFT'] = 'Περιεχόμενο';
$lang['el_GR']['LeftAndMain.ss']['APPVERSIONTEXT1'] = 'Αυτή είναι η';
$lang['el_GR']['LeftAndMain.ss']['APPVERSIONTEXT2'] = 'τρέχουσα έκδοση, τεχνικά είναι το CVS branch';
$lang['el_GR']['LeftAndMain.ss']['ARCHS'] = 'Αποθηκευμένος Ιστοχώρος';
$lang['el_GR']['LeftAndMain.ss']['DRAFTS'] = 'Πρόχειρος Ιστοχώρος';
$lang['el_GR']['LeftAndMain.ss']['EDIT'] = 'Επεξεργασία';
$lang['el_GR']['LeftAndMain.ss']['LOADING'] = 'Φόρτωση...';
$lang['el_GR']['LeftAndMain.ss']['LOGGEDINAS'] = 'Συνδεδεμένος σαν';
$lang['el_GR']['LeftAndMain.ss']['LOGOUT'] = 'αποσύνδεση';
$lang['el_GR']['LeftAndMain.ss']['PUBLIS'] = 'Δημοσιευμένος Ιστοχώρος';
$lang['el_GR']['LeftAndMain.ss']['SSWEB'] = 'Ιστοχώρος Silverstripe';
$lang['el_GR']['LeftAndMain.ss']['VIEWPAGEIN'] = 'Εμφάνιση Σελίδας:';
$lang['el_GR']['MemberList.ss']['FILTER'] = 'Φίλτρο';
$lang['el_GR']['MemberTableField.ss']['ADDNEW'] = 'Προσθήκη Νέου';
$lang['el_GR']['ReportAdmin_right.ss']['WELCOME1'] = 'Καλωσήρθατε στην';
$lang['el_GR']['ReportAdmin_right.ss']['WELCOME2'] = 'ενότητα αναφορών. Παρακαλώ επιλέξτε κάποια συγκεκριμένη αναφορά στα αριστερά.';
$lang['el_GR']['SecurityAdmin']['MENUTITLE'] = 'Ασφάλεια';
$lang['el_GR']['SecurityAdmin_right.ss']['WELCOME1'] = 'Καλωσήρθατε στην';
$lang['el_GR']['SecurityAdmin_right.ss']['WELCOME2'] = 'ενότητα διαχείρισης ασφάλειας. Παρακαλώ επιλέξτε ομάδα από τα αριστέρα.';
$lang['el_GR']['SideReport']['REPEMPTY'] = 'Η %s αναφορά είναι κενή.';
$lang['el_GR']['ViewArchivedEmail.ss']['HAVEASKED'] = 'Επιθυμείται να εμφανιστεί το περιεχόμενο του ιστοχώρου σας την ';

?>