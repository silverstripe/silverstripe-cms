<div class="cms-content-toolbar">
	<div class="cms-actions-buttons-row">
		<a class="cms-page-add-button ss-ui-button ss-ui-action-constructive tool-button" data-icon="add" href="$LinkPageAdd" data-url-addpage="{$LinkPageAdd('', 'ParentID=%s')}"><% _t('CMSMain.AddNewButton', 'Add new') %></a>

		<% if $View == 'Tree' %>
		<button class="cms-content-batchactions-button tool-button font-icon-list" data-toolid="batch-actions">
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
