<div class="preview-holder">
	<a class="preview" href="$URL" target="_blank">
		$URL
	</a>
	<% if not $IsReadonly %>
		<button type="button" class="ss-ui-button ss-ui-button-small edit">
			<% _t('URLSegmentField.Edit', 'Edit') %>
		</button>
	<% end_if %>
</div>
<div class="edit-holder">
	<input $AttributesHTML />
	<button type="button" class="update ss-ui-button-small">
		<% _t('URLSegmentField.OK', 'OK') %>
	</button>
	<button type="button" class="cancel ss-ui-button-small ss-ui-action-minor">
		<% _t('URLSegmentField.Cancel', 'Cancel') %>
	</button>
	<% if $HelpText %><p class="help">$HelpText</p><% end_if %>
</div>
