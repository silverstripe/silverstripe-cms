<%-- If we're editing a record, include the left panel and allow it to be collapsed --%>
<% if $CurrentRecord %>
    <div class="cms-content-tools fill-height cms-panel cms-panel-layout" data-expandOnClick="true" data-layout-type="border" id="cms-content-tools-CMSMain">
        <% include SilverStripe\\CMS\\Controllers\\CMSMain_LeftPanel %>
        <div class="cms-panel-content-collapsed">
            <h3 class="cms-panel-header">$CMSTreeTitle</h3>
        </div>
        <div class="toolbar toolbar--south cms-panel-toggle">
            <a class="toggle-expand" href="#"><span>&raquo;</span></a>
            <a class="toggle-collapse" href="#"><span>&laquo;</span></a>
        </div>
    </div>
<% end_if %>
