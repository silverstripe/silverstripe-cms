<div class="view-controls">
	<button id="filters-button" class="btn btn-secondary icon-button font-icon-search btn--icon-large no-text" title="<% _t('SilverStripe\CMS\Controllers\CMSPagesController.FILTER', 'Filter') %>"></button>
	<div class="icon-button-group">
		<%-- Change to data-pjax-target="Content-PageList" to enable in-edit listview --%>
		<a class="cms-panel-link icon-button font-icon-tree page-view-link <% if $ViewState == 'treeview' %>active<% end_if %>"
			href="$LinkTreeView.ATT"
			data-view="treeview"
			data-pjax-target="$PJAXTarget.ATT"
			title="<% _t('SilverStripe\CMS\Controllers\CMSPagesController.TreeView', 'Tree View') %>"
		></a><a class="cms-panel-link icon-button font-icon-list page-view-link <% if $ViewState == 'listview' %>active<% end_if %>"
			href="$LinkListView.ATT"
			data-view="listview"
			data-pjax-target="$PJAXTarget.ATT"
			title="<% _t('SilverStripe\CMS\Controllers\CMSPagesController.ListView', 'List View') %>"
		></a>
	</div>
</div>
