<div class="preview-holder">
    <a class="URL-link" href="$URL" target="_blank">
        $URL
    </a>
    <% if not $IsReadonly %>
    <button role="button" type="button" class="btn btn-outline-secondary btn-sm edit">
        <%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.Edit 'Edit' %>
    </button>
    <% end_if %>
</div>

<div class="edit-holder">
    <div class="input-group">
        <input $AttributesHTML />
        <div class="input-group-append">
            <button role="button" type="button" class="btn btn-primary update">
                <%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.OK 'OK' %>
            </button>
        </div>
        <div class="input-group-append">
            <button role="button" type="button" class="btn btn-outline-secondary btn-sm input-group-append cancel">
                <%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.Cancel 'Cancel' %>
            </button>
        </div>
    </div>
    <% if $HelpText %><p class="form__field-description">$HelpText</p><% end_if %>
</div>
