<div class="view-controls view-controls--{$ViewState}">
    <% if not $TreeIsFiltered %>
        <%-- Change to data-pjax-target="Content-PageList" to enable in-edit listview --%>
        <a class="page-view-link btn btn-secondary btn--icon-sm btn--no-text font-icon-tree"
            href="$LinkTreeView.ATT"
            data-view="treeview"
            data-pjax-target="$PJAXTarget.ATT"
            title="<%t SilverStripe\CMS\Controllers\CMSPagesController.TreeView 'Tree View' %>"
        ></a>

        <a class="page-view-link btn btn-secondary btn--icon-sm btn--no-text font-icon-list"
            href="$LinkListView.ATT"
            data-view="listview"
            data-pjax-target="$PJAXTarget.ATT"
            title="<%t SilverStripe\CMS\Controllers\CMSPagesController.ListView 'List View' %>"
        ></a>
    <% end_if %>
</div>
