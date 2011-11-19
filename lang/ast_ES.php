<?php

/**
 * Asturian language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('ast_ES', $lang) && is_array($lang['ast_ES'])) {
	$lang['ast_ES'] = array_merge($lang['en_US'], $lang['ast_ES']);
} else {
	$lang['ast_ES'] = $lang['en_US'];
}

$lang['ast_ES']['AssetAdmin']['MENUTITLE'] = 'Ficheros ya imaxes';
$lang['ast_ES']['AssetAdmin']['MENUTITLE'] = 'Ficheros ya imaxes';
$lang['ast_ES']['AssetAdmin_left.ss']['ENABLEDRAGGING'] = 'Permitir axeitar arrastrando y soltando';
$lang['ast_ES']['CMSMain']['ACCESS'] = 'Accesu a la estaya \'%s\'';
$lang['ast_ES']['CMSMain']['CANCEL'] = 'Encaboxar';
$lang['ast_ES']['CMSMain']['CHOOSEREPORT'] = '(Escueyi un informe)';
$lang['ast_ES']['CMSMain']['COMPARINGV'] = 'Comparando les versiones %s y %s';
$lang['ast_ES']['CMSMain']['COPYPUBTOSTAGE'] = '¿De verdá quies copiar el conteníu asoleyáu al sitiu de prueba?';
$lang['ast_ES']['CMSMain']['EMAIL'] = 'Corréu';
$lang['ast_ES']['CMSMain']['GO'] = 'Dir';
$lang['ast_ES']['CMSMain']['MENUTITLE'] = 'Conteníu del sitiu';
$lang['ast_ES']['CMSMain']['MENUTITLE'] = 'Páxines';
$lang['ast_ES']['CMSMain']['NEW'] = 'Nuevu';
$lang['ast_ES']['CMSMain']['NOCONTENT'] = 'ensin conteníu';
$lang['ast_ES']['CMSMain']['OK'] = 'Aceutar';
$lang['ast_ES']['CMSMain']['PAGENOTEXISTS'] = 'Esta páxina nun esiste';
$lang['ast_ES']['CMSMain']['PRINT'] = 'Imprentar';
$lang['ast_ES']['CMSMain']['PUBALLCONFIRM'] = 'Por favor, asoleya toles páxines del sitiu, copiando\'l conteníu en pruebes al sitiu activu';
$lang['ast_ES']['CMSMain']['PUBALLFUN'] = 'Función "Asoleyar too"';
$lang['ast_ES']['CMSMain']['PUBALLFUN2'] = 'Calcar esti botón ye l\'equivalente de dir a cada páxina y calcar "espublizar". Ta pensao pa usar dempués de facer ediciones masives del conteníu, como cuando se construyó\'l sitiu pola primer vegada.';
$lang['ast_ES']['CMSMain']['PUBPAGES'] = 'Fecho: Asoleyaes %d páxines';
$lang['ast_ES']['CMSMain']['REMOVEDFD'] = 'Desaniciao del sitiu borrador';
$lang['ast_ES']['CMSMain']['REMOVEDPAGE'] = 'Desaniciáu \'%s\' del sitiu asoleyáu';
$lang['ast_ES']['CMSMain']['RESTORED'] = 'Se restauró \'%s\' correutamente';
$lang['ast_ES']['CMSMain']['ROLLBACK'] = 'Devolver a esta versión';
$lang['ast_ES']['CMSMain']['ROLLEDBACKPUB'] = 'Devueltu a la versión asoleyada. El nuevu númberu de versión ye #%d';
$lang['ast_ES']['CMSMain']['ROLLEDBACKVERSION'] = 'Devueltu a la versión #%d. El nuevu númberu de versión ye #%d';
$lang['ast_ES']['CMSMain']['SAVE'] = 'Guardar';
$lang['ast_ES']['CMSMain']['TOTALPAGES'] = 'Páxines en total:';
$lang['ast_ES']['CMSMain']['VERSIONSNOPAGE'] = 'Nun se pue alcontrar la páxina #%d';
$lang['ast_ES']['CMSMain']['VIEWING'] = 'Tas viendo la versión #%s, creada %s por %s';
$lang['ast_ES']['CMSMain_left.ss']['BATCHACTIONS'] = 'Aiciones agrupaes';
$lang['ast_ES']['CMSMain_left.ss']['CREATE'] = 'Crear';
$lang['ast_ES']['CMSMain_left.ss']['ENABLEDRAGGING'] = 'Permitir axeitar arrastrando y soltando';
$lang['ast_ES']['CMSMain_left.ss']['SEARCH'] = 'Guetar';
$lang['ast_ES']['CMSMain_left.ss']['SITECONTENT TITLE'] = 'Árbol de páxines';
$lang['ast_ES']['CommentAdmin']['MENUTITLE'] = 'Comentarios';
$lang['ast_ES']['CommentAdmin']['MENUTITLE'] = 'Comentarios';
$lang['ast_ES']['LeftAndMain']['CHANGEDURL'] = 'La URL camudó a \'%s\'';
$lang['ast_ES']['LeftAndMain']['HELP'] = 'Ayuda';
$lang['ast_ES']['LeftAndMain']['PAGETYPE'] = 'Triba de páxina:';
$lang['ast_ES']['LeftAndMain']['PERMAGAIN'] = 'Terminó la to sesión nel CMS. Si quies volver a coneutar, escribi un nome d\'usuariu y una contraseña abaxo.';
$lang['ast_ES']['LeftAndMain']['PERMALREADY'] = 'Lo sentimos, pero nun tienes accesu a esa parte del CMS. Si quies coneutar con otru nome, failo debaxo';
$lang['ast_ES']['LeftAndMain']['PERMDEFAULT'] = 'Escueyi un métodu d\'autenticación y escribi les tos credenciales p\'acceder al CMS.';
$lang['ast_ES']['LeftAndMain']['SAVED'] = 'guardao';
$lang['ast_ES']['LeftAndMain']['SAVEDUP'] = 'Guardao';
$lang['ast_ES']['LeftAndMain']['SITECONTENTLEFT'] = 'Conteníu del sitiu';
$lang['ast_ES']['LeftAndMain.ss']['LOADING'] = 'Cargando...';
$lang['ast_ES']['LeftAndMain.ss']['REQUIREJS'] = 'El CMS necesita que tengas JavaScript activáu.';
$lang['ast_ES']['LeftAndMain']['STATUSPUBLISHEDSUCCESS'] = 'S\'espublizó \'%s\' correutamente';
$lang['ast_ES']['LeftAndMain']['STATUSTO'] = 'L\'estáu camudó a \'%s\'';
$lang['ast_ES']['MemberList.ss']['FILTER'] = 'Peñera';
$lang['ast_ES']['MemberTableField']['DeleteTitleText'] = 'Desaniciar d\'esti grupu';
$lang['ast_ES']['MemberTableField']['DeleteTitleTextDatabase'] = 'Desaniciar de la base de datos y de tolos grupos';
$lang['ast_ES']['MemberTableField.ss']['ADDNEW'] = 'Amestar nuevu';
$lang['ast_ES']['ModelAdmin']['CREATEBUTTON'] = 'Crear \'%s\'';
$lang['ast_ES']['PageComment']['COMMENTBY'] = 'Comentariu de \'%s\' tocante a %s';
$lang['ast_ES']['PageComment']['PLURALNAME'] = 'Comentarios de páxina';
$lang['ast_ES']['PageComment']['SINGULARNAME'] = 'Comentariu de páxina';
$lang['ast_ES']['ReportAdmin']['MENUTITLE'] = 'Informes';
$lang['ast_ES']['ReportAdmin_right.ss']['WELCOME1'] = 'Bienllegáu a';
$lang['ast_ES']['ReportAdmin_right.ss']['WELCOME2'] = 'estaya d\'informes. Escueyi un informe determináu de la izquierda';
$lang['ast_ES']['SecurityAdmin']['MENUTITLE'] = 'Seguridá';
$lang['ast_ES']['SecurityAdmin']['MENUTITLE'] = 'Seguridá';
$lang['ast_ES']['SecurityAdmin_left.ss']['ENABLEDRAGGING'] = 'Permitir axeitar arrastrando y soltando';
$lang['ast_ES']['SecurityAdmin_right.ss']['WELCOME1'] = 'Bienllegáu a';
$lang['ast_ES']['SecurityAdmin_right.ss']['WELCOME2'] = 'estaya d\'alministración de la seguridá. Escueyi un grupu de la izquierda.';
$lang['ast_ES']['SideReport']['REPEMPTY'] = 'L\'informe %s ta baleru.';
$lang['ast_ES']['ViewArchivedEmail.ss']['HAVEASKED'] = 'Pidisti ver el conteníu del nuesu sitiu el';

?>