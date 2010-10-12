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
		'CMSMAIN.ERRORUNFILTER' : 'Filterung des Seitenbaumes zurückgesetzt',
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
		'CMSMAIN.SELECTMOREPAGES' : "Sie haben %s Seiten ausgewählt.\n\nWollen Sie wirklich diese Aktion durchführen?",
		'ModelAdmin.SAVED': "Gespeichert",
		'ModelAdmin.REALLYDELETE': "Wirklich löschen?",
		'ModelAdmin.DELETED': "Gelöscht",
		'ModelAdmin.VALIDATIONERROR': "Validationsfehler",
		'LeftAndMain.PAGEWASDELETED': "Diese Seite wurde gelöscht.",
		'LeftAndMain.CONFIRMUNSAVED': "Sind Sie sicher, dasß Sie die Seite verlassen möchten?\n\nWARNUNG: Ihre Änderungen werden nicht gespeichert.\n\nDrücken Sie \"OK\" um fortzufahren, oder \"Abbrechen\" um auf dieser Seite zu bleiben.",
		'WidgetAreaEditor.TOOMANY': 'Sie haben die maximale Anzahl an Widgets in diesem Bereich erreicht.'
	});
}