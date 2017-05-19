<div class="preview-holder">
	<a class="URL-link" href="$URL" target="_blank">
		$URL
	</a>
	<% if not $IsReadonly %>
		<button role="button" type="button" class="btn btn-sm ui-button-text-only ss-ui-button edit">
			<%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.Edit 'Edit' %>
		</button>
	<% end_if %>
</div>
<div class="edit-holder">
	<input $AttributesHTML />
	<button role="button" data-icon="accept" type="button" class="btn btn-sm ui-button-text-icon-primary ss-ui-button update">
		<%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.OK 'OK' %>
	</button>
	<button role="button" data-icon="cancel" type="button" class="btn btn-sm ui-button-text-icon-secondary ss-ui-button cancel">
		<%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.Cancel 'Cancel' %>
	</button>
	<% if $HelpText %><p class="form__field-description">$HelpText</p><% end_if %>
</div>
