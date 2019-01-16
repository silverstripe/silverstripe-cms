<% if not $node.IsInDB %><%-- Only render root node if it's the true root --%>
    <ul><li id="record-0" data-id="0" class="Root nodelete"><ins class="jstree-icon font-icon-right-dir">&nbsp;</ins><strong>$rootTitle</strong>
<% end_if %>
<% if $limited %>
    <ul><li class="readonly">
        <span class="item">
            <%t SilverStripe\\CMS\\Controllers\\CMSMain.TOO_MANY_PAGES 'Too many pages' %>
            (<a href="{$listViewLink.ATT}" class="subtree-list-link" data-id="$node.ID" data-pjax-target="Content"><%t SilverStripe\\CMS\\Controllers\\CMSMain.SHOW_AS_LIST 'show as list' %></a>)
        </span>
    </li></ul>
<% else_if $children %>
    <ul>
        <% loop $children %><% include SilverStripe\\CMS\\Controllers\\CMSMain_TreeNode %><% end_loop %>
    </ul>
<% end_if %>
<% if not $node.IsInDB %>
    </li></ul>
<% end_if %>
