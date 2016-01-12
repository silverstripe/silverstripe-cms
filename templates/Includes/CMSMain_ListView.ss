<% include CMSPagesController_ContentToolActions %>

<div class="ss-dialog cms-page-add-form-dialog cms-dialog-content" id="cms-page-add-form" title="<% _t('CMSMain.AddNew', 'Add new page') %>">
	$AddForm
</div>

<div class="cms-panel-content center">
	<% if $TreeIsFiltered %>
	<div class="cms-notice cms-tree-filtered">
		<strong><% _t('CMSMain.ListFiltered', 'Showing search results.') %></strong>
		<a href="$LinkPages" class="cms-panel-link">
			<% _t('CMSMain.TreeFilteredClear', 'Clear') %>
		</a>

		<div class="cms-list" data-url-list="$Link(getListViewHTML)">
			$ListViewForm
		</div>
	</div>
	<% else %>

	<div class="cms-list" data-url-list="$Link(getListViewHTML)">
		$ListViewForm
	</div>
	<% end_if %>
</div>
