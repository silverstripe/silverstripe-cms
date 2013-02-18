if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
  if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('nl_NL', {
		'CMSMAIN.WARNINGSAVEPAGESBEFOREADDING' : "You have to save a page before adding children underneath it",
		'CMSMAIN.CANTADDCHILDREN' : "You can't add children to the selected node",
		'CMSMAIN.ERRORADDINGPAGE' : 'Error adding page',
		'CMSMAIN.FILTEREDTREE' : 'Filtered tree to only show changed pages',
		'CMSMAIN.ERRORFILTERPAGES' : 'Could not filter tree to only show changed pages<br />%s',
		'CMSMAIN.ERRORUNFILTER' : 'Ongefilterde structuur',
		'CMSMAIN.PUBLISHINGPAGES' : 'Paginas aan het publiceren...',
		'CMSMAIN.SELECTONEPAGE' : "Selecteer minstens 1 pagina.",
		'CMSMAIN.ERRORPUBLISHING' : 'Verwijder gepubliceerde paginas',
		'CMSMAIN.REALLYDELETEPAGES' : "Wil je echt de geselecteerde %s pagina's verwijderen?",
		'CMSMAIN.DELETINGPAGES' : 'Paginas verwijderen...',
		'CMSMAIN.ERRORDELETINGPAGES': 'Fout bij verwijderen paginas',
		'CMSMAIN.PUBLISHING' : 'Publiceren...',
		'CMSMAIN.RESTORING': 'Herstellen...',
		'CMSMAIN.ERRORREVERTING': 'Error reverting to live content',
		'CMSMAIN.SAVING' : 'opslaan...',
		'CMSMAIN.SELECTMOREPAGES' : "Je hebt pagina(s) %s geselecteerd. \n\nWil je deze actie uitvoeren?",
		'CMSMAIN.ALERTCLASSNAME': 'Het paginatype wordt aangepast na opslaan van de pagina',
		'CMSMAIN.URLSEGMENTVALIDATION': 'URLs kunnen alleen bestaan uit letters, cijfers en koppeltekens.',
		'AssetAdmin.BATCHACTIONSDELETECONFIRM': "Wil je deze mappen %s verwijderen?",
		'AssetTableField.REALLYDELETE': 'Wil je de geselecteerde bestanden verwijderen??',
		'AssetTableField.MOVING': 'Verplaats %s bestand(en)',
		'CMSMAIN.AddSearchCriteria': 'Voeg criteria toe',
		'WidgetAreaEditor.TOOMANY': 'Sorry, je hebt de maximaal aantal widgets bereikt',
		'AssetAdmin.ConfirmDelete': 'Wil je deze map verwijderen en alle bestanden??',
		'Folder.Name': 'Mapnaam',
		'Tree.AddSubPage': 'Voeg nieuwe pagina toe',
		'Tree.EditPage': 'Aanpassen',
		'CMSMain.ConfirmRestoreFromLive': "Do you really want to copy the published content to the draft site?",
		'CMSMain.RollbackToVersion': "Do you really want to roll back to version #%s of this page?",
		'URLSEGMENT.Edit': 'Aanpassen',
		'URLSEGMENT.OK': 'OK',
		'URLSEGMENT.Cancel': 'Annuleren'
	});
}
