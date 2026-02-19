<% include SilverStripe\\CMS\\Controllers\\CMSMain_ContentToolActions View='Tree' %>

$ExtraTreeTools

<% if $TreeIsFiltered %>
    <div class="cms-tree-filtered cms-notice flexbox-area-grow">
		<strong><%t SilverStripe\CMS\Controllers\CMSMain.TreeFiltered 'Showing search results.' %></strong>
		<a href="javascript:void(0)" class="clear-filter">
			<%t SilverStripe\CMS\Controllers\CMSMain.TreeFilteredClear 'Clear' %>
		</a>

		<nav class="cms-tree <% if $TreeIsFiltered %>filtered-list<% end_if %>" aria-label="<%t SilverStripe\CMS\Controllers\CMSMain.SITE_PAGE_NAVIGATION 'Site Page Navigation' %>"
			data-url-tree="$LinkWithSearch($Link('getsubtree')).ATT"
			data-url-savetreenode="$Link('savetreenode').ATT"
			data-url-updatetreenodes="$Link('updatetreenodes').ATT"
			data-url-addpage="{$Link('AddForm/?action_doAdd=1&ParentID=%s&RecordType=%s&ParentModeField=child').ATT}"
			data-url-editpage="$LinkRecordEdit('%s').ATT"
			data-url-duplicate="{$Link('duplicate/%s').ATT}"
			data-url-duplicatewithchildren="{$Link('duplicatewithchildren/%s').ATT}"
			data-url-listview="{$Link('?view=list').ATT}"
			data-hints="$TreeHints.ATT"
			data-childfilter="$Link('childfilter').ATT"
			data-extra-params="SecurityID=$SecurityID.ATT">
			$TreeAsUL
        </nav>
    </div>
<% else %>
    <nav class="cms-tree flexbox-area-grow <% if $TreeIsFiltered %>filtered-list<% end_if %>" aria-label="<%t SilverStripe\CMS\Controllers\CMSMain.SITE_PAGE_NAVIGATION 'Site Page Navigation' %>"
		data-url-tree="$LinkWithSearch($Link('getsubtree')).ATT"
		data-url-savetreenode="$Link('savetreenode').ATT"
		data-url-updatetreenodes="$Link('updatetreenodes').ATT"
		data-url-addpage="{$Link('AddForm/?action_doAdd=1&ParentID=%s&RecordType=%s&ParentModeField=child').ATT}"
		data-url-editpage="$LinkRecordEdit('%s').ATT"
		data-url-duplicate="{$Link('duplicate/%s').ATT}"
		data-url-duplicatewithchildren="{$Link('duplicatewithchildren/%s').ATT}"
		data-url-listview="{$Link('?view=list').ATT}"
		data-hints="$TreeHints.ATT"
		data-childfilter="$Link('childfilter').ATT"
		data-extra-params="SecurityID=$SecurityID.ATT">
		$TreeAsUL
	</nav>
<% end_if %>
