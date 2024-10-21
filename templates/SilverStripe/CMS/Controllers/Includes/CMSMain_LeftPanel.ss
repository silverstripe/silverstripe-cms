<div class="cms-content-header north vertical-align-items">
    <div class="cms-content-header-info fill-width vertical-align-items">
        <% if $TreeIsFiltered %>
            <% include SilverStripe\\Admin\\BackLink_Button Backlink=$BreadcrumbsBacklink %>
        <% end_if %>
        <% if $CurrentRecord %>
            <%-- Explicit breadcrumb item for this menu section --%>
            <div class="section-heading flexbox-area-grow">
                <span class="section-label">$MenuCurrentItem.Title</span>
            </div>
        <% else %>
            <%-- Full breadcrumbs (useful for tree view which isn't available when viewing an edit form) --%>
            <% include SilverStripe\\Admin\\CMSBreadcrumbs %>
        <% end_if %>
        <% include SilverStripe\\CMS\\Controllers\\CMSMain_Filter %>
    </div>
</div>

<div class="<% if $CurrentRecord %>panel panel--scrollable cms-panel-content<% else %>cms-content-fields ui-widget-content cms-panel-padded<% end_if %> flexbox-area-grow fill-height">
    <div class="cms-content-filters<% if not $TreeIsFiltered %> cms-content-filters--hidden<% end_if %>">
        <div class="search-holder search-holder--cms" data-schema="$SearchFieldSchema"></div>
    </div>
    $RecordList
</div>
