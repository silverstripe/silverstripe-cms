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
		'CMSMAIN.PUBLISHINGPAGES' : 'Pubblicazione delle pagine...',
		'CMSMAIN.SELECTONEPAGE' : "Selezionare almeno una pagine.",
		'CMSMAIN.ERRORPUBLISHING' : 'Errore durante la pubblicazione delle pagine',
		'CMSMAIN.REALLYDELETEPAGES' : "Si vuole veramente eliminare le %s pagine selezionate?",
		'CMSMAIN.DELETINGPAGES' : 'Eliminazione delle pagine...',
		'CMSMAIN.ERRORDELETINGPAGES': 'Errore durante l\'eliminazione delle pagine',
		'CMSMAIN.PUBLISHING' : 'Pubblicazione...',
		'CMSMAIN.RESTORING': 'Ripristino...',
		'CMSMAIN.ERRORREVERTING': 'Errore durante il ripristino verso un contenuto Live',
		'CMSMAIN.SAVING' : 'salvataggio...'
	});
}