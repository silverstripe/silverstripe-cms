<div class="toolbar toolbar--content cms-content-toolbar">
	<div class="btn-toolbar cms-actions-buttons-row">
        <% if not $TreeIsFiltered %>
            <a class="btn btn-primary cms-content-addpage-button tool-button font-icon-plus" href="$LinkRecordAdd" data-url-addpage="{$LinkRecordAdd('', 'ParentID=%s')}">
                <%t SilverStripe\Admin\\LeftAndMain.AddNew 'Add new {name}' name=$getRecord('singleton').i18n_singular_name().lowercase %>
            </a>

            <% if $View == 'Tree' %>
            <button type="button" class="cms-content-batchactions-button btn btn-secondary tool-button font-icon-check-mark-2 btn--last" data-toolid="batch-actions">
                <%t SilverStripe\CMS\Controllers\CMSPageHistoryController.MULTISELECT "Batch actions" %>
            </button>
            <% end_if %>
        <% end_if %>

        <% include SilverStripe\\CMS\\Controllers\\CMSMain_ViewControls PJAXTarget='Content-RecordList' %>
	</div>


	<div class="cms-actions-tools-row">
		<% if $View == 'Tree' %>
		<div id="batch-actions" class="cms-content-batchactions-dropdown tool-action">
			$BatchActionsForm
		</div>
		<% end_if %>
	</div>
</div>
