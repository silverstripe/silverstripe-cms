<?php

i18n::include_by_locale('es_ES');

global $lang;

$lang['es_ES'] = $lang['en_US'];

$lang['es_ES']['CMSMain']['CREATE'] = array(
	'Crear un ',
	PR_MEDIUM,
	'"Create a " message, followed by an action (e.g. "contact form")'
);
$lang['es_ES']['CMSMain']['REMOVEDFD'] = 'Eliminado del sitio de prueba';
$lang['es_ES']['CMSMain']['RESTORE'] = 'Restaurar';
$lang['es_ES']['CMSMain']['DELETEFP'] = 'Eliminar del sitio publicado';
$lang['es_ES']['CMSMain']['SAVE'] = 'Guardar';
$lang['es_ES']['CMSMain']['PAGENOTEXISTS'] = utf8_encode('Esta página no existe');
$lang['es_ES']['CMSMain']['NEW'] = array(
	'Nuevo ',
	PR_MEDIUM,
	'"New " followed by a className'
);
$lang['es_ES']['CMSMain']['RESTORED'] = array(
	utf8_encode('\'%s\' restaurado con éxito'),
	PR_MEDIUM,
	'Param %s is a title'
);
$lang['es_ES']['CMSMain']['SENTTO'] = array(
	'Enviado a %s %s para aprobación.',
	PR_LOW,
	'First param is first name, and second is surname'
);
$lang['es_ES']['CMSMain']['WORKTODO'] = utf8_encode('Tienes trabajo que hacer en estas <b>%d</b> páginas.');
$lang['es_ES']['CMSMain']['NOTHINGASSIGNED'] = 'No tienes nada asignado.';
$lang['es_ES']['CMSMain']['WAITINGON'] = utf8_encode('Estás esperando que otra gente trabaje en estas <b>%d</b> páginas.');
$lang['es_ES']['CMSMain']['NOWAITINGON'] = utf8_encode('No estás esperando de nadie.');
$lang['es_ES']['CMSMain']['CHOOSEREPORT'] = '(Escoge un informe)';
$lang['es_ES']['CMSMain']['VERSIONSNOPAGE'] = array(
	utf8_encode('No se puede encontrar la página #%d'),
	PR_LOW
);
$lang['es_ES']['CMSMain']['ROLLEDBACKVERSION'] = utf8_encode('Se ha vuelto a la versión #%d.  El nuevo número de versión es el #%d');
$lang['es_ES']['CMSMain']['ROLLEDBACKPUB'] = utf8_encode('Se a vuelto a la versión publicada. El nuevo número de versión es el #%d');
$lang['es_ES']['CMSMain']['REMOVEDPAGE'] = utf8_encode('Se ha borrado \'%s\' del sitio publicado');
$lang['es_ES']['CMSMain']['VIEWING'] = utf8_encode('Estás viendo la versión #%d, creada %s');
$lang['es_ES']['CMSMain']['EMAIL'] = 'Correo';
$lang['es_ES']['CMSMain']['PRINT'] = 'Imprimir';
$lang['es_ES']['CMSMain']['ROLLBACK'] = utf8_encode('Volver a esta versión');
$lang['es_ES']['CMSMain']['COMPARINGV'] = utf8_encode('Estás comparando las versiones #%d y #%d');
$lang['es_ES']['CMSMain']['COPYPUBTOSTAGE'] = utf8_encode('Quieres realmente copiar el contenido publicado al sitio temporal?');
$lang['es_ES']['CMSMain']['OK'] = 'Aceptar';
$lang['es_ES']['CMSMain']['CANCEL'] = 'Cancelar';
$lang['es_ES']['CMSMain']['PAGEDEL'] = utf8_encode('%d página eliminada ');
$lang['es_ES']['CMSMain']['PAGESDEL'] = utf8_encode('%d páginas eliminadas ');
$lang['es_ES']['CMSMain']['NOWBROKEN'] = utf8_encode('  Las siguientes páginas tienen ahora enlaces rotos:');
$lang['es_ES']['CMSMain']['NOWBROKEN2'] = utf8_encode('Se ha enviado un correo a sus propietarios y ellos arreglarán estas páginas.');
$lang['es_ES']['CMSMain']['GO'] = 'Ir';
$lang['es_ES']['CMSMain']['NOCONTENT'] = 'sin contenido';
$lang['es_ES']['CMSMain']['TOTALPAGES'] = utf8_encode('Páginas totales: ');
$lang['es_ES']['CMSMain']['PUBPAGES'] = utf8_encode('Hecho: %d páginas publicadas');
$lang['es_ES']['CMSMain']['PUBALLFUN'] = utf8_encode('Funcionalidad de "Publicar todo"');
$lang['es_ES']['CMSMain']['PUBALLFUN2'] = utf8_encode('Apretar este botón hará el equivalente a ir a cada página y apretar "publicar". Está para cuando hay ediciones masivas del contenido, como cuando el sitio se construye por primera vez.');
$lang['es_ES']['CMSMain']['PUBALLCONFIRM'] = array(
	utf8_encode('Por favor publica todas las páginas del sitio, copiando el contenido de prueba a la página en directo'),
	PR_LOW,
	'Confirmation button'
);
$lang['es_ES']['CMSMain']['VISITRESTORE'] = array(
	'visita restorepage/(ID)',
	PR_LOW,
	'restorepage/(ID) should not be translated (is an URL)'
);
$lang['es_ES']['LeftAndMain']['PERMDEFAULT'] = utf8_encode('Por favor, elige un método de autentificación i introduce tus credenciales para acceder al CMS.');
$lang['es_ES']['LeftAndMain']['PERMALREADY'] = utf8_encode('Lamentablemente no puedes acceder a ésta parte del CMS. Si quieres entrar como alguien distinto, puedes hacerlo debajo.');
$lang['es_ES']['LeftAndMain']['PERMAGAIN'] = utf8_encode('Has salido del CMS. Si quieres volver a entrar de nuevo, introduce usuario y contraseña debajo.');
$lang['es_ES']['LeftAndMain']['HELLO'] = array(
	'Contenido del web',
	PR_HIGH,
	'Menu title'
);
$lang['es_ES']['LeftAndMain']['FILESIMAGES'] = array(
	utf8_encode('Archivos e imágenes'),
	PR_HIGH,
	'Menu title'
);
$lang['es_ES']['LeftAndMain']['NEWSLETTERS'] = array(
	'Boletines de noticias',
	PR_HIGH,
	'Menu title'
);
$lang['es_ES']['LeftAndMain']['REPORTS'] = array(
	'Informes',
	PR_HIGH,
	'Menu title'
);
$lang['es_ES']['LeftAndMain']['SECURITY'] = array(
	'Seguridad',
	PR_HIGH,
	'Menu title'
);
$lang['es_ES']['LeftAndMain']['STATISTICS'] = array(
	utf8_encode('Estadísticas'),
	PR_HIGH,
	'Menu title'
);
$lang['es_ES']['LeftAndMain']['HELP'] = array(
	'Ayuda',
	PR_HIGH,
	'Menu title'
);
$lang['es_ES']['LeftAndMain']['PAGETYPE'] = utf8_encode('Tipo de página: ');
$lang['es_ES']['LeftAndMain']['SITECONTENT'] = array(
	'Contenido',
	PR_HIGH,
	'Root node on left'
);
$lang['es_ES']['LeftAndMain']['SAVEDUP'] = 'Guardado';
$lang['es_ES']['LeftAndMain']['CHANGEDURL'] = '  URL cambiada a \'%s\'';
$lang['es_ES']['LeftAndMain']['STATUSTO'] = '  Estado cambiado a \'%s\'';
$lang['es_ES']['LeftAndMain']['SAVED'] = 'guardado';
$lang['es_ES']['LeftAndMain']['PLEASESAVE'] = utf8_encode('Por favor, guarde la página: esta página no puede actualizarse porque aún no ha sido guardada.');
$lang['es_ES']['LeftAndMain']['REQUESTERROR'] = utf8_encode('Error en la petición');
$lang['es_ES']['CMSMain_left.ss']['OPENBOX'] = 'haga clic para abrir la caja';
$lang['es_ES']['CMSMain_left.ss']['CLOSEBOX'] = 'haga clic para cerrar la caja';
$lang['es_ES']['CMSMain_left.ss']['SITECONTENT TITLE'] = array(
	'Contenido del web y estructura',
	PR_HIGH
);
$lang['es_ES']['CMSMain_left.ss']['CREATE'] = array(
	'Crear...',
	PR_HIGH
);
$lang['es_ES']['CMSMain_left.ss']['DELETE'] = array(
	'Eliminar...',
	PR_HIGH
);
$lang['es_ES']['CMSMain_left.ss']['REORDER'] = array(
	'Reordenar...',
	PR_HIGH
);
$lang['es_ES']['CMSMain_left.ss']['SELECTPAGESDEL'] = utf8_encode('Seleccione las páginas que quiere eliminar y luego pulse el botón de debajo');
$lang['es_ES']['CMSMain_left.ss']['KEY'] = 'Leyenda:';
$lang['es_ES']['CMSMain_left.ss']['DELETECONFIRM'] = utf8_encode('Eliminar las páginas seleccionadas');
$lang['es_ES']['CMSMain_left.ss']['DRAGPAGES'] = utf8_encode('Para reorganizar el sitio, arrastre las páginas como desee.');
$lang['es_ES']['CMSMain_left.ss']['SELECTPAGESDUP'] = utf8_encode('Seleccione las páginas que quiera duplicar, si sus hijos se deberían incluir, y dónde quiere poner los duplicados');
$lang['es_ES']['CMSMain_left.ss']['ADDEDNOTPUB'] = utf8_encode('Añadido al sitio de prueba y no publicado aún');
$lang['es_ES']['CMSMain_left.ss']['NEW'] = 'nuevo';
$lang['es_ES']['CMSMain_left.ss']['DELETEDSTILLLIVE'] = utf8_encode('Eliminado del sitio de prueba pero aún en el sitio en directo');
$lang['es_ES']['CMSMain_left.ss']['DEL'] = 'eliminado';
$lang['es_ES']['CMSMain_left.ss']['EDITEDNOTPUB'] = utf8_encode('Editado en el sitio de prueba y no publicado aún');
$lang['es_ES']['CMSMain_left.ss']['CHANGED'] = 'cambiado';
$lang['es_ES']['CMSMain_left.ss']['CLICKOPEN'] = 'haga clic para abrir la caja';
$lang['es_ES']['CMSMain_left.ss']['CLICKCLOSE'] = 'haga clic para cerrar la caja';
$lang['es_ES']['CMSMain_left.ss']['TASKLIST'] = 'Lista de tareas';
$lang['es_ES']['CMSMain_left.ss']['WAITINGON'] = 'Esperando';
$lang['es_ES']['CMSMain_left.ss']['PAGEVERSIONH'] = utf8_encode('Historial de versiones de páginas');
$lang['es_ES']['CMSMain_left.ss']['COMPAREMODE'] = utf8_encode('Modo de comparación (seleccione dos debajo)');
$lang['es_ES']['CMSMain_left.ss']['SHOWUNPUB'] = 'Mostrar versiones no publicadas';
$lang['es_ES']['CMSMain_left.ss']['COMMENTS'] = 'Comentarios';
$lang['es_ES']['CMSMain_left.ss']['SITEREPORTS'] = 'Informes del sitio';
$lang['es_ES']['CMSMain_left.ss']['GO'] = 'Ir';
$lang['es_ES']['CMSMain_right.ss']['SENDTO'] = 'Enviar a';
$lang['es_ES']['CMSMain_right.ss']['LOADING'] = 'cargando...';
$lang['es_ES']['CMSMain_right.ss']['STATUS'] = 'Estado';
$lang['es_ES']['CMSMain_right.ss']['ANYMESSAGE'] = 'Tiene algun mensaje para su editor?';
$lang['es_ES']['CMSMain_right.ss']['MESSAGE'] = 'Mensaje';
$lang['es_ES']['CMSMain_right.ss']['SUBMIT'] = utf8_encode('Enviar para aprobación');
$lang['es_ES']['CMSMain_right.ss']['WELCOMETO'] = 'Bienvenido a';
$lang['es_ES']['CMSMain_right.ss']['CHOOSEPAGE'] = utf8_encode('Por favor, escoja una página de la izquierda.');
$lang['es_ES']['CMSRight.ss']['WELCOMETO'] = 'Bienvenido a';
$lang['es_ES']['CMSRight.ss']['CHOOSEPAGE'] = utf8_encode('Por favor, escoja una página de la izquierda.');
$lang['es_ES']['LeftAndMain.ss']['LOADING'] = array(
	'Cargando...',
	PR_HIGH
);
$lang['es_ES']['LeftAndMain.ss']['SSWEB'] = 'Sitio web de Silverstripe';
$lang['es_ES']['LeftAndMain.ss']['APPVERSIONTEXT1'] = utf8_encode('Ésta es la versión de');
$lang['es_ES']['LeftAndMain.ss']['APPVERSIONTEXT2'] = utf8_encode('que está actualmente corriendo, técnicamente es la branca CVS');
$lang['es_ES']['LeftAndMain.ss']['LOGGEDINAS'] = 'Conectado como';
$lang['es_ES']['LeftAndMain.ss']['LOGOUT'] = 'salir';
$lang['es_ES']['LeftAndMain.ss']['VIEWPAGEIN'] = utf8_encode('Vista de página:');
$lang['es_ES']['LeftAndMain.ss']['SWITCHTO'] = 'Cambiar a:';
$lang['es_ES']['LeftAndMain.ss']['EDIT'] = 'Editar';
$lang['es_ES']['LeftAndMain.ss']['DRAFTS'] = 'Sitio de prueba';
$lang['es_ES']['LeftAndMain.ss']['PUBLIS'] = 'Sitio publicado';
$lang['es_ES']['LeftAndMain.ss']['ARCHS'] = 'Sitio archivado';

?>