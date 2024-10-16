<div
    class="panel panel--padded panel--scrollable flexbox-area-grow fill-height flexbox-display cms-content-view cms-tree-view-sidebar cms-panel-deferred"
    data-url="$LinkTreeViewDeferred"
    data-url-treeview="$LinkTreeViewDeferred"
    data-url-listview="$LinkListViewDeferred"
    data-url-listviewroot="$LinkListViewRoot"
    data-no-ajax="<% if $TreeIsFiltered %>true<% else %>false<% end_if %>"
>
    <% if $TreeIsFiltered %>
        <% include SilverStripe\\CMS\\Controllers\\CMSMain_ListView %>
    <% else %>
        <%-- Lazy-loaded via ajax --%>
    <% end_if %>
</div>
