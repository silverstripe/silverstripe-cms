if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('de_DE', {
		'CMSMAIN.WARNINGSAVEPAGESBEFOREADDING' : "Sie müssen diese Seite speichern bevor Unterseiten hingefügt werden können",
		'CMSMAIN.CANTADDCHILDREN' : "Unterseiten nicht erlaubt",
		'CMSMAIN.ERRORADDINGPAGE' : 'Fehler beim Hinzufügen der Seite',
		'CMSMAIN.FILTEREDTREE' : 'Gefilterter Seitenbaum zeigt nur Änderungen',
		'CMSMAIN.ERRORFILTERPAGES' : 'Konnte Seitenbaum nicht filtern<br />%s',
		'CMSMAIN.ERRORUNFILTER' : 'Konnte Filterung des Seitenbaumes nicht aufheben<br />%s',
		'CMSMAIN.PUBLISHINGPAGES' : 'Publiziere Seiten...',
		'CMSMAIN.SELECTONEPAGE' : "Bitte mindestens eine Seite auswählen",
		'CMSMAIN.ERRORPUBLISHING' : 'Fehler beim Veröffentlichen der Seiten',
		'CMSMAIN.REALLYDELETEPAGES' : "Wollen Sie wirklich %s Seiten löschen?",
		'CMSMAIN.DELETINGPAGES' : 'Lösche Seiten...',
		'CMSMAIN.ERRORDELETINGPAGES': 'Fehler beim Löschen der Seiten',
		'CMSMAIN.PUBLISHING' : 'Veröffentliche...',
		'CMSMAIN.RESTORING': 'Wiederherstellen...',
		'CMSMAIN.ERRORREVERTING': 'Fehler beim Wiederherstellen des Live-Inhaltes',
		'CMSMAIN.SAVING' : 'Sichern...',
		'CMSMAIN.SELECTMOREPAGES' : "Sie haben %s Seiten ausgewählt.\n\nWollen Sie wirklich diese Aktion durchführen?"
	});
}