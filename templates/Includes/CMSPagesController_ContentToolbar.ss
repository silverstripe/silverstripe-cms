<div class="cms-actions-row">
	<a class="cms-page-add-button ss-ui-button ss-ui-action-constructive" data-icon="add" href="$LinkPageAdd" data-url-addpage="{$LinkPageAdd('?ParentID=%s')}"><% _t('CMSMain.AddNewButton', 'Add new') %></a>
</div>

<div class="cms-content-batchactions">
	<div class="view-mode-batchactions-wrapper">
		<input id="view-mode-batchactions" name="view-mode-batchactions" type="checkbox" />
		<label for="view-mode-batchactions"><% _t("CMSPagesController_ContentToolbar.ss.MULTISELECT","Multi-selection") %></label>
	</div>

	$BatchActionsForm
</div>
