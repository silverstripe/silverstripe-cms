<div class="view-controls view-controls--{$ViewState}">
    <% if not $TreeIsFiltered %>
        <%-- Change to data-pjax-target="Content-RecordList" to enable in-edit listview --%>
        <a class="page-view-link btn btn-secondary btn--icon-sm btn--no-text"
            href="$LinkTreeView.ATT"
            data-view="treeview"
            data-pjax-target="$PJAXTarget.ATT"
            title="<%t SilverStripe\CMS\Controllers\CMSMain.TreeView 'Tree View' %>"
            aria-label="<%t SilverStripe\CMS\Controllers\CMSMain.TreeView 'Tree View' %>"
        ><span class="font-icon-tree" aria-hidden="true"></span></a>

        <a class="page-view-link btn btn-secondary btn--icon-sm btn--no-text"
            href="$LinkListView.ATT"
            data-view="listview"
            data-pjax-target="$PJAXTarget.ATT"
            title="<%t SilverStripe\CMS\Controllers\CMSMain.ListView 'List View' %>"
            aria-label="<%t SilverStripe\CMS\Controllers\CMSMain.ListView 'List View' %>"
        ><span class="font-icon-list" aria-hidden="true"></span></a>
    <% end_if %>
</div>
