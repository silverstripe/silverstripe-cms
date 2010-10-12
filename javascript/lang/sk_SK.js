if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sk_SK', {
		'CMSMAIN.WARNINGSAVEPAGESBEFOREADDING' : "Pred pridaním ďalšej podstránky, musíte stránku uložiť",
		'CMSMAIN.CANTADDCHILDREN' : "Nemôžete pridať potomkov do vybratého uzla",
		'CMSMAIN.ERRORADDINGPAGE' : 'Chyba pri pridaní stránky',
		'CMSMAIN.FILTEREDTREE' : 'Filtrovaná štruktúra k zobrazeniu iba zmenených stránok',
		'CMSMAIN.ERRORFILTERPAGES' : 'Nie je možné filtrovať štruktúru k zobrazeniu iba zmenených stránok<br />%s',
		'CMSMAIN.ERRORUNFILTER' : 'Nie je možné filtrovať štruktúru webu<br />%s',
		'CMSMAIN.ERRORUNFILTER' : 'Nefiltrovaná štruktúra',
		'CMSMAIN.PUBLISHINGPAGES' : 'Zverejňovanie stránok...',
		'CMSMAIN.SELECTONEPAGE' : "Prosím, vyberte najmenej 1 stránku.",
		'CMSMAIN.ERRORPUBLISHING' : 'Chyba pri zverejňovaní stránok',
		'CMSMAIN.REALLYDELETEPAGES' : "Skutočne chcete zmazať %s označené stránky?",
		'CMSMAIN.DELETINGPAGES' : 'Mazanie stránok...',
		'CMSMAIN.ERRORDELETINGPAGES': 'Chyba pri mazaní stránok',
		'CMSMAIN.PUBLISHING' : 'Zverejňovanie...',
		'CMSMAIN.RESTORING': 'Obnovovanie...',
		'CMSMAIN.ERRORREVERTING': 'Chyba prevádzania na živý obsah',
		'CMSMAIN.SAVING' : 'ukladanie...',
		'CMSMAIN.SELECTMOREPAGES' : "Máte vybraté %s stránok.\n\nSkutočne ich chcete?",
		'ModelAdmin.SAVED': "Uložené",
		'ModelAdmin.REALLYDELETE': "Skutočně chcete zmazať?",
		'ModelAdmin.DELETED': "Zmazané",
		'LeftAndMain.PAGEWASDELETED': "Táto stránka bola zmazaná. Pre editáciu stránky, vyberte ju vľavo.",
		'LeftAndMain.CONFIRMUNSAVED': "Určite chcete opustiť navigáciu z tejto stránky?\n\nUPOZORNENIE: Vaše zmeny neboli uložené.\n\nStlačte OK pre pokračovať, alebo Cancel, ostanete na teto stránke."
	});
}