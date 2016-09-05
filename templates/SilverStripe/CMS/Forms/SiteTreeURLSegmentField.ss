<div class="preview-holder">
	<a class="preview" href="$URL" target="_blank">
		$URL
	</a>
	<% if not $IsReadonly %>
		<button role="button" type="button" class="btn ui-button-text-only ss-ui-button edit">
			<% _t('URLSegmentField.Edit', 'Edit') %>
		</button>
	<% end_if %>
</div>
<div class="edit-holder">
	<input $AttributesHTML />
	<button role="button" data-icon="accept" type="button" class="btn ui-button-text-icon-primary ss-ui-button update">
		<% _t('URLSegmentField.OK', 'OK') %>
	</button>
	<button role="button" data-icon="cancel" type="button" class="btn ui-button-text-icon-secondary ss-ui-button cancel">
		<% _t('URLSegmentField.Cancel', 'Cancel') %>
	</button>
	<% if $HelpText %><p class="form__field-description">$HelpText</p><% end_if %>
</div>
