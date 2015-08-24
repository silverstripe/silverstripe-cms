<% include CMSPagesController_ContentToolActions View='Tree' %>

<div class="ss-dialog cms-page-add-form-dialog cms-dialog-content" id="cms-page-add-form" title="<% _t('CMSMain.AddNew', 'Add new page') %>">
	$AddForm
</div>

$ExtraTreeTools

<div class="center">
	<% if $TreeIsFiltered %>
	<div class="cms-tree-filtered cms-notice">
		<strong><% _t('CMSMain.TreeFiltered', 'Showing search results.') %></strong>
		<a href="javascript:void(0)" class="clear-filter">
			<% _t('CMSMain.TreeFilteredClear', 'Clear') %>
		</a>

		<div class="cms-tree <% if $TreeIsFiltered %>filtered-list<% end_if %>"
			data-url-tree="$LinkWithSearch($Link(getsubtree))"
			data-url-savetreenode="$Link(savetreenode)"
			data-url-updatetreenodes="$Link(updatetreenodes)"
			data-url-addpage="{$LinkPageAdd('AddForm/?action_doAdd=1', 'ParentID=%s&amp;PageType=%s')}"
			data-url-editpage="$LinkPageEdit('%s')"
			data-url-duplicate="{$Link('duplicate/%s')}"
			data-url-duplicatewithchildren="{$Link('duplicatewithchildren/%s')}"
			data-url-listview="{$Link('?view=list')}"
			data-hints="$SiteTreeHints.XML"
			data-childfilter="$Link('childfilter')"
			data-extra-params="SecurityID=$SecurityID">
			$SiteTreeAsUL
		</div>
	</div>

	<% else %>

	<div class="cms-tree <% if $TreeIsFiltered %>filtered-list<% end_if %>"
		data-url-tree="$LinkWithSearch($Link(getsubtree))"
		data-url-savetreenode="$Link(savetreenode)"
		data-url-updatetreenodes="$Link(updatetreenodes)"
		data-url-addpage="{$LinkPageAdd('AddForm/?action_doAdd=1', 'ParentID=%s&amp;PageType=%s')}"
		data-url-editpage="$LinkPageEdit('%s')"
		data-url-duplicate="{$Link('duplicate/%s')}"
		data-url-duplicatewithchildren="{$Link('duplicatewithchildren/%s')}"
		data-url-listview="{$Link('?view=list')}"
		data-hints="$SiteTreeHints.XML"
		data-childfilter="$Link('childfilter')"
		data-extra-params="SecurityID=$SecurityID">
		$SiteTreeAsUL
	</div>

	<% end_if %>
</div>
