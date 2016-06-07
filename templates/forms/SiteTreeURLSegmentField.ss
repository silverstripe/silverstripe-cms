<div class="preview-holder">
	<a class="preview" href="$URL" target="_blank">
		$URL
	</a>
	<% if not $IsReadonly %>
		<button type="button" class="btn btn-secondary-outline btn-sm edit">
			<% _t('URLSegmentField.Edit', 'Edit') %>
		</button>
	<% end_if %>
</div>
<div class="edit-holder">
	<input $AttributesHTML />
	<button type="button" class="btn btn-primary update">
		<% _t('URLSegmentField.OK', 'OK') %>
	</button>
	<button type="button" class="btn btn-secondary cancel">
		<% _t('URLSegmentField.Cancel', 'Cancel') %>
	</button>
	<% if $HelpText %><p class="form__field-description">$HelpText</p><% end_if %>
</div>
