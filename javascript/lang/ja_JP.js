if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('ja_JP', {
		'CMSMAIN.WARNINGSAVEPAGESBEFOREADDING' : "子ページを追加する前に，そのページを保存する必要があります．",
		'CMSMAIN.CANTADDCHILDREN' : "選択されたノードには子供を追加することはできません．",
		'CMSMAIN.ERRORADDINGPAGE' : 'ページの追加でエラーが起きました．',
		'CMSMAIN.FILTEREDTREE' : '変更されたページのみ表示するフィルタされたツリー',
		'CMSMAIN.ERRORFILTERPAGES' : '変更されたページのみ表示するようにツリーをフィルタできませんでした<br />%s',
		'CMSMAIN.ERRORUNFILTER' : 'フィルタされていないツリー',
		'CMSMAIN.PUBLISHINGPAGES' : 'ページを公開しています...',
		'CMSMAIN.SELECTONEPAGE' : "最低でも1ページ選択してください．",
		'CMSMAIN.ERRORPUBLISHING' : 'ページを公開中にエラー',
		'CMSMAIN.REALLYDELETEPAGES' : "Do you really want to delete the %s marked pages?",
		'CMSMAIN.DELETINGPAGES' : 'ページを削除しています',
		'CMSMAIN.ERRORDELETINGPAGES': 'ページ削除においてエラー',
		'CMSMAIN.PUBLISHING' : '公開しています...',
		'CMSMAIN.RESTORING': '復元しています...',
		'CMSMAIN.ERRORREVERTING': 'Error reverting to live content',
		'CMSMAIN.SAVING' : '保存しています...',
		'CMSMAIN.SELECTMOREPAGES' : "You have %s pages selected.\n\nDo you really want to perform this action?",
		'CMSMAIN.ALERTCLASSNAME': 'ページ保存後にページの種類は更新されます．',
		'CMSMAIN.URLSEGMENTVALIDATION': 'URLはアルファベットの文字か数値，及びハイフンのみから構成されます．',
		'AssetAdmin.BATCHACTIONSDELETECONFIRM': "%sフォルダを本当に削除しますか?",
		'AssetTableField.REALLYDELETE': 'マークされているファイルを本当に削除しますか?',
		'AssetTableField.MOVING': '%sファイルを移動中．',
		'CMSMAIN.AddSearchCriteria': 'Add Criteria',
		'WidgetAreaEditor.TOOMANY': '申し訳ございません．このエリアにおけるウィジェットの最大数に到達しました．',
		'AssetAdmin.ConfirmDelete': 'このフォルダとフォルダに含まれるすべてのファイルを本当に削除しますか?',
		'Folder.Name': 'フォルダ名',
		'Tree.AddSubPage': 'ここに新しいページを追加',
		'Tree.EditPage': '編集',
		'CMSMain.ConfirmRestoreFromLive': "公開されているコンテンツを下書きサイトへ本当にコピーしますか?",
		'CMSMain.RollbackToVersion': "このページのバージョン#%sへ本当にロールバックしますか?"
	});
}
