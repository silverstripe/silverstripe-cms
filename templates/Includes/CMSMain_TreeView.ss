<div class="cms-content-toolbar">
	<% include CMSPagesController_ContentToolActions %>
	<% include CMSPagesController_ContentToolbar %>
</div>

<div class="ss-dialog cms-page-add-form-dialog cms-dialog-content" id="cms-page-add-form" title="<% _t('CMSMain.AddNew', 'Add new page') %>">
	$AddForm
</div>

$ExtraTreeTools

<div class="center">
	<% if $TreeIsFiltered %>
	<div class="cms-tree-filtered cms-notice">
		<strong><% _t('CMSMain.TreeFiltered', 'Filtered tree.') %></strong>
		<a href="$LinkPages" class="cms-panel-link">
			<% _t('CMSMain.TreeFilteredClear', 'Clear filter') %>
		</a>
	</div>
	<% end_if %>

	<div class="cms-tree" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)" data-url-updatetreenodes="$Link(updatetreenodes)" data-url-addpage="{$LinkPageAdd('AddForm/?action_doAdd=1', 'ParentID=%s&amp;PageType=%s')}" data-url-editpage="$LinkPageEdit('%s')" data-url-duplicate="{$Link('duplicate/%s')}" data-url-duplicatewithchildren="{$Link('duplicatewithchildren/%s')}" data-url-listview="{$Link('?view=list')}" data-hints="$SiteTreeHints.XML" data-extra-params="SecurityID=$SecurityID">
		$SiteTreeAsUL
	</div>
</div>
