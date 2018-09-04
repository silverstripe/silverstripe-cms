<% include SilverStripe\\CMS\\Controllers\\CMSPagesController_ContentToolActions %>

<div class="ss-dialog cms-page-add-form-dialog cms-dialog-content" id="cms-page-add-form" title="<%t SilverStripe\\CMS\\Controllers\\CMSMain.AddNew 'Add new page' %>">
	$AddForm
</div>

<div class="cms-panel-content center">
	<div class="cms-list" data-url-list="$Link('getListViewHTML').ATT">
		$ListViewForm
	</div>
</div>
