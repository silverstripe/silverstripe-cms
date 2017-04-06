<!-- Only render root node if it's the true root -->
<% if not $node.IsInDB %>
    <ul><li id="record-0" data-id="0" class="Root nodelete"><strong>$rootTitle</strong>
<% end_if %>
<% if $limited %>
    <ul><li class="readonly">
        <span class="item">
            <%t LeftAndMain.TooManyPages 'Too many pages' %>
            (<a href="{$listViewLink.ATT}" class="cms-panel-link" data-pjax-target="Content">
                <%t LeftAndMain.ShowAsList 'show as list' %>
            </a>)
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
