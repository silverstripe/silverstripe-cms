<% if $ViewState == 'listview' %>
    <div class="flexbox-area-grow cms-content-view">
        <% include SilverStripe\\CMS\\Controllers\\CMSMain_ListView %>
    </div>
<% else %>
    <% include SilverStripe\\CMS\\Controllers\\CMSMain_TreeView_Deferred %>
<% end_if %>
