<div class="toolbar toolbar--content cms-content-toolbar">
	<div class="btn-toolbar cms-actions-buttons-row">
		<a class="btn btn-primary cms-content-addpage-button tool-button font-icon-plus" href="$LinkPageAdd" data-url-addpage="{$LinkPageAdd('', 'ParentID=%s')}"><% _t('SilverStripe\CMS\Controllers\CMSMain.AddNewButton', 'Add new') %></a>

		<% if $View == 'Tree' %>
		<button type="button" class="cms-content-batchactions-button btn btn-secondary tool-button font-icon-check-mark-2 btn--last" data-toolid="batch-actions">
			<% _t("SilverStripe\CMS\Controllers\CMSPageHistoryController.MULTISELECT","Batch actions") %>
		</button>
		<% end_if %>

        <% include SilverStripe\\CMS\\Controllers\\CMSMain_ViewControls PJAXTarget='Content-PageList' %>
	</div>


	<div class="cms-actions-tools-row">
		<% if $View == 'Tree' %>
		<div id="batch-actions" class="cms-content-batchactions-dropdown tool-action">
			$BatchActionsForm
		</div>
		<% end_if %>
	</div>
</div>
