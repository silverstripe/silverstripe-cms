<% include SilverStripe\\CMS\\Controllers\\CMSPagesController_ContentToolActions %>

<div class="ss-dialog cms-page-add-form-dialog cms-dialog-content" id="cms-page-add-form" title="<%t SilverStripe\\CMS\\Controllers\\CMSMain.AddNew 'Add new page' %>">
	$AddForm
</div>

<div class="cms-panel-content center">
	<% if $TreeIsFiltered %>
	<div class="cms-notice cms-tree-filtered">
		<strong><%t SilverStripe\\CMS\\Controllers\\CMSMain.ListFiltered 'Showing search results.' %></strong>
		<a href="$LinkPages.ATT" class="cms-panel-link cms-clear-filter">
			<%t SilverStripe\\CMS\\Controllers\\CMSMain.TreeFilteredClear 'Clear' %>
		</a>

		<div class="cms-list" data-url-list="$Link('getListViewHTML')">
			$ListViewForm
		</div>
	</div>
	<% else %>

	<div class="cms-list" data-url-list="$Link('getListViewHTML').ATT">
		$ListViewForm
	</div>
	<% end_if %>
</div>
