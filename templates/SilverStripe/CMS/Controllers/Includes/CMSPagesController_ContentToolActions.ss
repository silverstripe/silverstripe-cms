<div class="cms-content-toolbar">
	<div class="cms-actions-buttons-row">
		<a class="ss-ui-button cms-content-addpage-button tool-button font-icon-plus" href="$LinkPageAdd" data-url-addpage="{$LinkPageAdd('', 'ParentID=%s')}"><% _t('CMSMain.AddNewButton', 'Add new') %></a>

		<% if $View == 'Tree' %>
		<button type="button" class="cms-content-batchactions-button tool-button font-icon-check-mark-2" data-toolid="batch-actions">
			<% _t("CMSPagesController_ContentToolbar_ss.MULTISELECT","Batch actions") %>
		</button>
		<% end_if %>
	</div>

	<div class="cms-actions-tools-row">
		<% if $View == 'Tree' %>
		<div id="batch-actions" class="cms-content-batchactions-dropdown tool-action">
			$BatchActionsForm
		</div>
		<% end_if %>
	</div>
</div>
