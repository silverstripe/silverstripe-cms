if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('it_IT', {
		'CMSMAIN.WARNINGSAVEPAGESBEFOREADDING' : "È necessario salvare la pagina prima di aggiungerci dei figli",
		'CMSMAIN.CANTADDCHILDREN' : "Non è possibile aggiungere figli al nodo selezionato",
		'CMSMAIN.ERRORADDINGPAGE' : 'Errori durante l\'aggiunta della pagina',
		'CMSMAIN.FILTEREDTREE' : 'Filtrare l\'albero per visualizzare solo le pagine modificate',
		'CMSMAIN.ERRORFILTERPAGES' : 'Impossibile filtrare l\'albero per visualizzare solo le pagine modificate<br />%s',
		'CMSMAIN.ERRORUNFILTER' : 'Impossibile visualizzate l\'insieme e l\'albero del sito<br />%s',
		'CMSMAIN.ERRORUNFILTER' : 'Albero non filtrato',
		'CMSMAIN.PUBLISHINGPAGES' : 'Pubblicazione delle pagine...',
		'CMSMAIN.SELECTONEPAGE' : "Selezionare almeno una pagine.",
		'CMSMAIN.ERRORPUBLISHING' : 'Errore durante la pubblicazione delle pagine',
		'CMSMAIN.REALLYDELETEPAGES' : "Si vuole veramente eliminare le %s pagine selezionate?",
		'CMSMAIN.DELETINGPAGES' : 'Eliminazione delle pagine...',
		'CMSMAIN.ERRORDELETINGPAGES': 'Errore durante l\'eliminazione delle pagine',
		'CMSMAIN.PUBLISHING' : 'Pubblicazione...',
		'CMSMAIN.RESTORING': 'Ripristino...',
		'CMSMAIN.ERRORREVERTING': 'Errore durante il ripristino verso un contenuto Live',
		'CMSMAIN.SAVING' : 'salvataggio...',
		'ModelAdmin.SAVED': "Salvato",
		'ModelAdmin.REALLYDELETE': "Si è sicuri di voler eliminare?",
		'ModelAdmin.DELETED': "Eliminato",
		'LeftAndMain.PAGEWASDELETED': "Questa pagina è stata eliminata. Per modificare questa pagine, selezionarla a sinistra.",
		'LeftAndMain.CONFIRMUNSAVED': "Siete sicuri di voler uscire da questa pagina?\n\nATTENZIONE: I vostri cambiamenti non sono stati salvati.\n\nCliccare OK per continuare, o su Annulla per rimanere sulla pagina corrente."
	});
}