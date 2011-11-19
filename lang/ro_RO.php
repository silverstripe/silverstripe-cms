<?php

/**
 * Romanian (Romania) language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('ro_RO', $lang) && is_array($lang['ro_RO'])) {
	$lang['ro_RO'] = array_merge($lang['en_US'], $lang['ro_RO']);
} else {
	$lang['ro_RO'] = $lang['en_US'];
}

$lang['ro_RO']['AssetAdmin']['CHOOSEFILE'] = 'Alege fişier ';
$lang['ro_RO']['AssetAdmin']['DELETEDX'] = '%s fişiere şterse. %s';
$lang['ro_RO']['AssetAdmin']['FILESREADY'] = 'Fişiere gata de a fi încărcate:';
$lang['ro_RO']['AssetAdmin']['MENUTITLE'] = 'Fişiere şi imagini';
$lang['ro_RO']['AssetAdmin']['MENUTITLE'] = 'Fisiere & Imagini';
$lang['ro_RO']['AssetAdmin']['MOVEDX'] = '%s fişiere mutate';
$lang['ro_RO']['AssetAdmin']['NEWFOLDER'] = 'DosarNou';
$lang['ro_RO']['AssetAdmin']['NOTHINGTOUPLOAD'] = 'Nu a fost nimic de încărcat';
$lang['ro_RO']['AssetAdmin']['NOWBROKEN'] = 'Următoarele pagini au legături rupte:';
$lang['ro_RO']['AssetAdmin']['SAVEDFILE'] = 'Fişierul %s a fost salvat';
$lang['ro_RO']['AssetAdmin']['SAVEFOLDERNAME'] = 'Salvează nume dosar';
$lang['ro_RO']['AssetAdmin']['UPLOAD'] = 'Încarcă Fişierele Listate Mai Jos';
$lang['ro_RO']['AssetAdmin']['UPLOADEDX'] = '%s fişiere încărcate';
$lang['ro_RO']['AssetAdmin_left.ss']['ENABLEDRAGGING'] = 'Permite reordonarea prin drag &amp; drop';
$lang['ro_RO']['AssetAdmin_left.ss']['FILESYSTEMSYNC'] = 'Cauta fișiere noi';
$lang['ro_RO']['AssetAdmin_left.ss']['TOREORG'] = 'Pentru reorganizarea dosarelor, trage-le oriunde, după cum doreşti.';
$lang['ro_RO']['AssetAdmin_right.ss']['CHOOSEPAGE'] = 'Va rugăm alegeţi o pagină din stânga';
$lang['ro_RO']['AssetAdmin_right.ss']['WELCOME'] = 'Bine aţi venit la';
$lang['ro_RO']['AssetAdmin_uploadiframe.ss']['PERMFAILED'] = 'Nu aveţi permisiunea să încărcaţi fişiere în acest dosar.';
$lang['ro_RO']['AssetTableField']['DIM'] = 'Dimensiuni';
$lang['ro_RO']['AssetTableField']['FILENAME'] = 'Nume fişier ';
$lang['ro_RO']['AssetTableField']['NOLINKS'] = 'Nici o pagină nu conţine legături spre acest fişier.';
$lang['ro_RO']['AssetTableField']['OWNER'] = 'ProprietarNici';
$lang['ro_RO']['AssetTableField']['PAGESLINKING'] = 'Următoarele pagini au legătură spre acest fişier';
$lang['ro_RO']['AssetTableField']['SIZE'] = 'Mărime ';
$lang['ro_RO']['AssetTableField.ss']['DELFILE'] = 'Şterge acest fişier';
$lang['ro_RO']['AssetTableField.ss']['DRAGTOFOLDER'] = 'Trage-l către dosarul din stânga pentru aa muta fişierul';
$lang['ro_RO']['AssetTableField']['TITLE'] = 'Titlu';
$lang['ro_RO']['AssetTableField']['TYPE'] = 'Tip';
$lang['ro_RO']['CMSMain']['CANCEL'] = 'Anulează';
$lang['ro_RO']['CMSMain']['CHOOSEREPORT'] = '(Alegeti un raport)';
$lang['ro_RO']['CMSMain']['COMPARINGV'] = 'Compari versiunea #%d cu #%d';
$lang['ro_RO']['CMSMain']['COPYPUBTOSTAGE'] = 'Chiar vrei să copiezi conţinutul publicat către stage site?';
$lang['ro_RO']['CMSMain']['EMAIL'] = 'Email';
$lang['ro_RO']['CMSMain']['GO'] = 'Start';
$lang['ro_RO']['CMSMain']['MENUTITLE'] = 'Conţinut site';
$lang['ro_RO']['CMSMain']['MENUTITLE'] = 'Continut Site';
$lang['ro_RO']['CMSMain']['METADESCOPT'] = 'Descriere';
$lang['ro_RO']['CMSMain']['METAKEYWORDSOPT'] = 'Cuvinte cheie';
$lang['ro_RO']['CMSMain']['NEW'] = 'Nou';
$lang['ro_RO']['CMSMain']['NOCONTENT'] = 'fără conţinut';
$lang['ro_RO']['CMSMain']['OK'] = 'OK';
$lang['ro_RO']['CMSMain']['PAGENOTEXISTS'] = 'Pagina nu exista';
$lang['ro_RO']['CMSMain']['PRINT'] = 'Print';
$lang['ro_RO']['CMSMain']['PUBALLCONFIRM'] = 'Te rog publică fiecare pagină din site, copiind stadiul către live. ';
$lang['ro_RO']['CMSMain']['PUBPAGES'] = 'Gata: %d pagini publicate';
$lang['ro_RO']['CMSMain']['REMOVEDFD'] = 'Şters din site-ul draft';
$lang['ro_RO']['CMSMain']['REMOVEDPAGE'] = 'Şters \'%s\' din site-ul publicat';
$lang['ro_RO']['CMSMain']['RESTORED'] = '\'%s\' a fost restaurat cu succes';
$lang['ro_RO']['CMSMain']['ROLLBACK'] = 'Intoarcere la acesta versiune';
$lang['ro_RO']['CMSMain']['ROLLEDBACKPUB'] = 'Intoarcere la versiunea publicata. Numarul noii versiuni e #%d';
$lang['ro_RO']['CMSMain']['ROLLEDBACKVERSION'] = 'Intoarcere la versiunea #%d. Numarul noii versiuni e #%d';
$lang['ro_RO']['CMSMain']['SAVE'] = 'Salvează';
$lang['ro_RO']['CMSMain']['STATUSOPT'] = 'Statut';
$lang['ro_RO']['CMSMain']['TOTALPAGES'] = 'Total pagini:';
$lang['ro_RO']['CMSMain']['VERSIONSNOPAGE'] = 'Nu gasesc pagina #%d';
$lang['ro_RO']['CMSMain']['VIEWING'] = 'Vizualizezi versiunea #%d, creata pe';
$lang['ro_RO']['CMSMain_left.ss']['ADDSEARCHCRITERIA'] = 'Adaugă Criteriu...';
$lang['ro_RO']['CMSMain_left.ss']['BATCHACTIONS'] = 'Acţiuni automate';
$lang['ro_RO']['CMSMain_left.ss']['CHANGED'] = 'schimbat';
$lang['ro_RO']['CMSMain_left.ss']['CLOSEBOX'] = 'click pentru a închide această căsuţă';
$lang['ro_RO']['CMSMain_left.ss']['COMPAREMODE'] = 'Mod comparare (click 2 mai jos)';
$lang['ro_RO']['CMSMain_left.ss']['CREATE'] = 'Creează';
$lang['ro_RO']['CMSMain_left.ss']['DEL'] = 'şters';
$lang['ro_RO']['CMSMain_left.ss']['DELETECONFIRM'] = 'Şterge paginile selectate';
$lang['ro_RO']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'Permite reordonarea prin drag &amp; drop';
$lang['ro_RO']['CMSMain_left.ss']['KEY'] = 'Cheie:';
$lang['ro_RO']['CMSMain_left.ss']['NEW'] = 'nou';
$lang['ro_RO']['CMSMain_left.ss']['OPENBOX'] = 'click pentru a deschide această căsuţă ';
$lang['ro_RO']['CMSMain_left.ss']['PUBLISHCONFIRM'] = 'Publică paginile selectate';
$lang['ro_RO']['CMSMain_left.ss']['SEARCH'] = 'Căutare';
$lang['ro_RO']['CMSMain_left.ss']['SHOWONLYCHANGED'] = 'Afişează doar paginile schimbate';
$lang['ro_RO']['CMSMain_left.ss']['SHOWUNPUB'] = 'Arată versiunile nepublicate';
$lang['ro_RO']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Conţinut site şi structură';
$lang['ro_RO']['CMSMain_left.ss']['SITEREPORTS'] = 'Rapoarte Site';
$lang['ro_RO']['CMSMain_right.ss']['CHOOSEPAGE'] = 'Va rugăm alegeţi o pagină din stânga';
$lang['ro_RO']['CMSMain_right.ss']['WELCOMETO'] = 'Bine ati venit la';
$lang['ro_RO']['CMSMain_versions.ss']['AUTHOR'] = 'Autor';
$lang['ro_RO']['CMSMain_versions.ss']['NOTPUB'] = 'Ne publicat';
$lang['ro_RO']['CMSMain_versions.ss']['UNKNOWN'] = 'Necunoscut';
$lang['ro_RO']['CMSMain_versions.ss']['WHEN'] = 'Când';
$lang['ro_RO']['CommentAdmin']['MENUTITLE'] = 'Comentarii';
$lang['ro_RO']['CommentAdmin']['MENUTITLE'] = 'Comentarii';
$lang['ro_RO']['ImageEditor.ss']['ROT'] = 'roteşte ';
$lang['ro_RO']['ImageEditor.ss']['SAVE'] = 'salvează&nbsp;imagine';
$lang['ro_RO']['ImageEditor.ss']['UNTITLED'] = 'Document Fără Titlu';
$lang['ro_RO']['LeftAndMain']['CHANGEDURL'] = 'URL-ul a fost schimbat in \'%s\'';
$lang['ro_RO']['LeftAndMain']['HELP'] = 'Ajutor';
$lang['ro_RO']['LeftAndMain']['PAGETYPE'] = 'Tip pagină: ';
$lang['ro_RO']['LeftAndMain']['PERMALREADY'] = 'Ne pare rău, dar nu puteţi accesa acea parte a CMS-ului. Daca doriţi să va autentificaţi ca altcineva, faceţi-o mai jos.';
$lang['ro_RO']['LeftAndMain']['PERMDEFAULT'] = 'Vă rugăm alegeţi o metodă de autentificare si introduceţi credenţialele pentru a accesa CMS-ul.';
$lang['ro_RO']['LeftAndMain']['PLEASESAVE'] = 'Va Rugam Salvaţi Pagina: Această pagină nu a putut fi actualizată deoarece încă nu a fost  salvată.';
$lang['ro_RO']['LeftAndMain']['REQUESTERROR'] = 'Eroare in cerere';
$lang['ro_RO']['LeftAndMain']['SAVED'] = 'salvat';
$lang['ro_RO']['LeftAndMain']['SAVEDUP'] = 'Salvat';
$lang['ro_RO']['LeftAndMain']['SITECONTENTLEFT'] = 'Conţinut site';
$lang['ro_RO']['LeftAndMain.ss']['APPVERSIONTEXT1'] = 'Acesta este';
$lang['ro_RO']['LeftAndMain.ss']['ARCHS'] = 'Site Arhivat';
$lang['ro_RO']['LeftAndMain.ss']['DRAFTS'] = 'Site Ciornă';
$lang['ro_RO']['LeftAndMain.ss']['EDIT'] = 'Editează ';
$lang['ro_RO']['LeftAndMain.ss']['EDITPROFILE'] = 'Profil';
$lang['ro_RO']['LeftAndMain.ss']['LOADING'] = 'Se încarcă...';
$lang['ro_RO']['LeftAndMain.ss']['LOGGEDINAS'] = 'Autentificat ca';
$lang['ro_RO']['LeftAndMain.ss']['PUBLIS'] = 'Site Publicat';
$lang['ro_RO']['LeftAndMain.ss']['REQUIREJS'] = 'Este necesar sa fie activata functia JavaScript.';
$lang['ro_RO']['LeftAndMain.ss']['SSWEB'] = 'Silverstripe Website';
$lang['ro_RO']['LeftAndMain.ss']['VIEWPAGEIN'] = 'Vizualizări pagină:';
$lang['ro_RO']['LeftAndMain']['STATUSTO'] = 'Statut schimbat in \'%s\'';
$lang['ro_RO']['MemberList.ss']['FILTER'] = 'Filtru';
$lang['ro_RO']['MemberList_Table.ss']['EMAIL'] = 'Adresă Email';
$lang['ro_RO']['MemberList_Table.ss']['FN'] = 'Prenume';
$lang['ro_RO']['MemberList_Table.ss']['PASSWD'] = 'Parolă ';
$lang['ro_RO']['MemberList_Table.ss']['SN'] = 'Nume';
$lang['ro_RO']['MemberTableField']['ADD'] = 'Adaugă ';
$lang['ro_RO']['MemberTableField']['ADDEDTOGROUP'] = 'Membrul a fost adăugat in grup';
$lang['ro_RO']['MemberTableField.ss']['ADDNEW'] = 'Adaugă nou';
$lang['ro_RO']['ModelAdmin']['CREATEBUTTON'] = 'Creaza \'%s\'';
$lang['ro_RO']['PageComment']['COMMENTBY'] = 'Comentariu de \'%s\' pe %s';
$lang['ro_RO']['PageComment']['PLURALNAME'] = 'Pagina Comentarii';
$lang['ro_RO']['PageComment']['SINGULARNAME'] = 'Pagina Comentariu';
$lang['ro_RO']['ReportAdmin']['MENUTITLE'] = 'Rapoarte';
$lang['ro_RO']['ReportAdmin_left.ss']['REPORTS'] = 'Rapoarte';
$lang['ro_RO']['ReportAdmin_right.ss']['WELCOME1'] = 'Bine aţi venit la';
$lang['ro_RO']['ReportAdmin_right.ss']['WELCOME2'] = 'secţiune rapoarte. Va rugăm alegeţi un raport specific din stânga.';
$lang['ro_RO']['ReportAdmin_SiteTree.ss']['REPORTS'] = 'Rapoarte';
$lang['ro_RO']['SecurityAdmin']['ADDMEMBER'] = 'Adaugă membru';
$lang['ro_RO']['SecurityAdmin']['MENUTITLE'] = 'Securitate';
$lang['ro_RO']['SecurityAdmin']['MENUTITLE'] = 'Securitate';
$lang['ro_RO']['SecurityAdmin']['NEWGROUP'] = 'Grup Nou';
$lang['ro_RO']['SecurityAdmin']['SAVE'] = 'Salvează ';
$lang['ro_RO']['SecurityAdmin']['SGROUPS'] = 'Grupuri de securitate';
$lang['ro_RO']['SecurityAdmin_left.ss']['CREATE'] = 'Creează ';
$lang['ro_RO']['SecurityAdmin_left.ss']['DEL'] = 'Şterge';
$lang['ro_RO']['SecurityAdmin_left.ss']['DELGROUPS'] = 'Şterge grupurile selectate';
$lang['ro_RO']['SecurityAdmin_left.ss']['ENABLEDRAGGING'] = 'Permite reordonarea prin drag &amp; drop';
$lang['ro_RO']['SecurityAdmin_left.ss']['SELECT'] = 'Selectaţi paginile care vreţi să le ştergeţi si apoi daţi click pe butonul de mai jos';
$lang['ro_RO']['SecurityAdmin_right.ss']['WELCOME1'] = 'Bine aţi venit la';
$lang['ro_RO']['SecurityAdmin_right.ss']['WELCOME2'] = 'secţiune de administrare a securităţii. Vă rugăm alegeţi un grup din stânga.';
$lang['ro_RO']['SideReport']['EMPTYPAGES'] = 'Pagini goale';
$lang['ro_RO']['SideReport']['LAST2WEEKS'] = 'Pagini editate in ultimele 2 săptămâni';
$lang['ro_RO']['SideReport']['REPEMPTY'] = 'Raportul %s e gol.';
$lang['ro_RO']['StaticExporter']['BASEURL'] = 'URL Bază';
$lang['ro_RO']['StaticExporter']['FOLDEREXPORT'] = 'Dosarul în care doriţi să exportaţi';
$lang['ro_RO']['ViewArchivedEmail.ss']['CANACCESS'] = 'Puteţi accesa site-ul arhivat la adresa:';
$lang['ro_RO']['ViewArchivedEmail.ss']['HAVEASKED'] = 'Vi s-a cerut să vedeţi conţinutul site-ului nostru pe data de';

?>