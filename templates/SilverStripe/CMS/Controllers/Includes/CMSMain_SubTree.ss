<% if not $node.IsInDB %><%-- Only render root node if it's the true root --%>
    <ul role="tree"><li id="record-0" data-id="0" class="Root nodelete" role="none"><span class="jstree-icon jstree-icon--arrow"<% if $count %> role="button" aria-label="{$toggleSiteTreeLabel.ATT}" aria-expanded="<% if $opened %>true<% else %>false<% end_if %>"<% else %> aria-hidden="true"<% end_if %>><span class="font-icon-right-dir" aria-hidden="true"></span>&nbsp;</span>
        <strong tabindex="-1" role="treeitem" aria-level="1"<% if $count %> aria-expanded="<% if $opened %>true<% else %>false<% end_if %>"<% end_if %>>$rootTitle</strong>
<% end_if %>
<% if $limited %>
    <ul id="subtree-<% if $node.IsInDB %>$node.ID<% else %>0<% end_if %>" role="group"><li class="readonly" role="none">
        <span class="item">
            <%t SilverStripe\\CMS\\Controllers\\CMSMain.TOO_MANY_RECORDS 'Too many records' %>
            (<a href="{$listViewLink.ATT}" class="subtree-list-link" data-id="$node.ID" data-pjax-target="Content"><%t SilverStripe\\CMS\\Controllers\\CMSMain.SHOW_AS_LIST 'show as list' %></a>)
        </span>
    </li></ul>
<% else_if $children %>
    <ul id="subtree-<% if $node.IsInDB %>$node.ID<% else %>0<% end_if %>" role="group">
        <% loop $children %><% include SilverStripe\\CMS\\Controllers\\CMSMain_TreeNode Controller=$Top.Controller %><% end_loop %>
    </ul>
<% end_if %>
<% if not $node.IsInDB %>
    </li></ul>
<% end_if %>
