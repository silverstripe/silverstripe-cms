<% include SilverStripe\\CMS\\Controllers\\CMSPagesController_ContentToolActions View='Tree' %>

<div class="ss-dialog cms-page-add-form-dialog cms-dialog-content" id="cms-page-add-form" title="<%t SilverStripe\Admin\LeftAndMain.AddNew 'Add new {name}' name=$getRecord('singleton').i18n_singular_name().lowercase %>">
	$AddForm
</div>

$ExtraTreeTools

<% if $TreeIsFiltered %>
    <div class="cms-tree-filtered cms-notice flexbox-area-grow">
		<strong><%t SilverStripe\CMS\Controllers\CMSMain.TreeFiltered 'Showing search results.' %></strong>
		<a href="javascript:void(0)" class="clear-filter">
			<%t SilverStripe\CMS\Controllers\CMSMain.TreeFilteredClear 'Clear' %>
		</a>

		<div class="cms-tree <% if $TreeIsFiltered %>filtered-list<% end_if %>"
			data-url-tree="$LinkWithSearch($Link('getsubtree')).ATT"
			data-url-savetreenode="$Link('savetreenode').ATT"
			data-url-updatetreenodes="$Link('updatetreenodes').ATT"
			data-url-addpage="{$LinkRecordAdd('AddForm/?action_doAdd=1', 'ParentID=%s&RecordType=%s').ATT}"
			data-url-editpage="$LinkRecordEdit('%s').ATT"
			data-url-duplicate="{$Link('duplicate/%s').ATT}"
			data-url-duplicatewithchildren="{$Link('duplicatewithchildren/%s').ATT}"
			data-url-listview="{$Link('?view=list').ATT}"
			data-hints="$TreeHints.ATT"
			data-childfilter="$Link('childfilter').ATT"
			data-extra-params="SecurityID=$SecurityID.ATT">
			$TreeAsUL
        </div>
    </div>
<% else %>
    <div class="cms-tree flexbox-area-grow <% if $TreeIsFiltered %>filtered-list<% end_if %>"
		data-url-tree="$LinkWithSearch($Link('getsubtree')).ATT"
		data-url-savetreenode="$Link('savetreenode').ATT"
		data-url-updatetreenodes="$Link('updatetreenodes').ATT"
		data-url-addpage="{$LinkRecordAdd('AddForm/?action_doAdd=1', 'ParentID=%s&RecordType=%s').ATT}"
		data-url-editpage="$LinkRecordEdit('%s').ATT"
		data-url-duplicate="{$Link('duplicate/%s').ATT}"
		data-url-duplicatewithchildren="{$Link('duplicatewithchildren/%s').ATT}"
		data-url-listview="{$Link('?view=list').ATT}"
		data-hints="$TreeHints.ATT"
		data-childfilter="$Link('childfilter').ATT"
		data-extra-params="SecurityID=$SecurityID.ATT">
		$TreeAsUL
	</div>
<% end_if %>
