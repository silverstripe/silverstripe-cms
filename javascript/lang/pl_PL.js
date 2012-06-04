if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('pl_PL', {
		'CMSMAIN.WARNINGSAVEPAGESBEFOREADDING' : "Należy najpierw zapisać stronę, aby móc dodać strony podrzędne",
		'CMSMAIN.CANTADDCHILDREN' : "Nie można dodać stron podrzędnych",
		'CMSMAIN.ERRORADDINGPAGE' : 'Błąd przy dodawaniu strony',
		'CMSMAIN.FILTEREDTREE' : 'Drzewo filtrowane pokazujące tylko zmienione strony',
		'CMSMAIN.ERRORFILTERPAGES' : 'Nie można było filtrować drzewa<br />%s',
		'CMSMAIN.ERRORUNFILTER' : 'Nie można było cofnąć filtrowania drzewa<br />%s',
		'CMSMAIN.PUBLISHINGPAGES' : 'Publikacja strony...',
		'CMSMAIN.SELECTONEPAGE' : "Proszę wybrać przynajmniej jedną stronę",
		'CMSMAIN.ERRORPUBLISHING' : 'Błąd podczas publikacji stron',
		'CMSMAIN.REALLYDELETEPAGES' : "Czy na pewno zaznaczone strony %s usunąć?",
		'CMSMAIN.DELETINGPAGES' : 'Usuwanie stron...',
		'CMSMAIN.ERRORDELETINGPAGES': 'Błąd podczas usuwania stron',
		'CMSMAIN.PUBLISHING' : 'Publikacja...',
		'CMSMAIN.RESTORING': 'Odzyskiwanie...',
		'CMSMAIN.ERRORREVERTING': 'Błąd podczas powrotu do opublikowanej strony',
		'CMSMAIN.SAVING' : 'Zapisywanie...'
	});
}