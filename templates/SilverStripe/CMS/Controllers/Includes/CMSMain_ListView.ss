<% include SilverStripe\\CMS\\Controllers\\CMSMain_ContentToolActions %>

<div class="ss-dialog cms-page-add-form-dialog cms-dialog-content" id="cms-page-add-form" title="<%t SilverStripe\Admin\\LeftAndMain.AddNew 'Add new {name}' name=$getRecord('singleton').i18n_singular_name().lowercase %>">
	$AddForm
</div>

<div class="cms-panel-content center">
	<div class="cms-list" data-url-list="$Link('getListViewHTML').ATT">
		$ListViewForm
	</div>
</div>
