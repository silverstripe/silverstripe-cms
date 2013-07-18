<div class="preview-holder">
	<a class="preview" href="$URL" target="_blank">
		$URL
	</a>
	<button class="ss-ui-button ss-ui-button-small edit">
		<% _t('URLSegmentField.Edit', 'Edit') %>
	</button>
</div>
<div class="edit-holder">
	<input $AttributesHTML />
	<button class="update ss-ui-button-small">
		<% _t('URLSegmentField.OK', 'OK') %>
	</button>
	<button class="cancel ss-ui-button-small ss-ui-action-minor">
		<% _t('URLSegmentField.Cancel', 'Cancel') %>
	</button>
	<% if $HelpText %><p class="help">$HelpText</p><% end_if %>
</div>