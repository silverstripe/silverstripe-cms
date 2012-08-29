if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sv_SE', {
		'CMSMAIN.WARNINGSAVEPAGESBEFOREADDING' : "Du måste spara en sida innan du kan lägga till undersidor.",
		'CMSMAIN.CANTADDCHILDREN' : "Du kan inte lägga till undersidor till den valda sidan.",
		'CMSMAIN.ERRORADDINGPAGE' : 'Ett fel uppstod när sidan skulle läggas till',
		'CMSMAIN.FILTEREDTREE' : 'Filtrerat träd för att visa enbart ändrade sidor',
		'CMSMAIN.ERRORFILTERPAGES' : 'Kunde inte filtrera trädet för att visa enbart ändrade sidor<br />%s',
		'CMSMAIN.ERRORUNFILTER' : 'Ofiltrerat träd',
		'CMSMAIN.PUBLISHINGPAGES' : 'Publicerar sidor...',
		'CMSMAIN.SELECTONEPAGE' : "Vänligen välj åtminståne 1 sida.",
		'CMSMAIN.ERRORPUBLISHING' : 'Ett fel uppstod när sidorna skulle publiceras',
		'CMSMAIN.REALLYDELETEPAGES' : "Vill du verkligen radera de %s markerade sidorna?",
		'CMSMAIN.DELETINGPAGES' : 'Raderar sidor...',
		'CMSMAIN.ERRORDELETINGPAGES': 'Ett fel uppstod när sidorna skulle raderas',
		'CMSMAIN.PUBLISHING' : 'Publicerar...',
		'CMSMAIN.RESTORING': 'Återställer...',
		'CMSMAIN.ERRORREVERTING': 'Ett fel uppstod vid återgång till publicerat innehåll',
		'CMSMAIN.SAVING' : 'sparar...',
		'CMSMAIN.SELECTMOREPAGES' : "Du har valt %s sidor.\n\nVill du verkligen utföra denna åtgärd?",
		'CMSMAIN.ALERTCLASSNAME': 'Sidtypen kommer att uppdateras efter att sidan sparats',
		'CMSMAIN.URLSEGMENTVALIDATION': 'URLar kan endast innehålla bokstäver, siffror och bindesträck.',
		'AssetAdmin.BATCHACTIONSDELETECONFIRM': "Vill du verkligen radera %s mappar?",
		'AssetTableField.REALLYDELETE': 'Vill du verkligen radera de markerade filerna?',
		'AssetTableField.MOVING': 'Flyttar %s fil(er)',
		'CMSMAIN.AddSearchCriteria': 'Lägg till kriterie',
		'WidgetAreaEditor.TOOMANY': 'Du har tyvärr nått max antal widgetar i detta område.',
		'AssetAdmin.ConfirmDelete': 'Vill du verkligen radera denna mapp och alla filer i den?',
		'Folder.Name': 'Mappnamn',
		'Tree.AddSubPage': 'Lägg till ny sida här',
		'Tree.EditPage': 'Editera',
		'CMSMain.ConfirmRestoreFromLive': "Vill du verkligen kopiera det publicerade innehållet till utkastsajten?",
		'CMSMain.RollbackToVersion': "Vill du verkligen gå tillbaka till version %s av denna sida?"
	});
}
