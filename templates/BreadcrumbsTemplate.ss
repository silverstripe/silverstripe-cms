<%-- Loop is all on one line to prevent whitespace bug in older versions of IE --%>
<% if $Pages %>
	<% loop $Pages %>
        <% if $IsLast %>
            <% if $hasField('MenuTitle') %>
                $MenuTitle.XML
            <% else %>
                $Title.XML
            <% end_if %>
        <% else %>
            <% if not Up.Unlinked %><a href="$Link" class="breadcrumb-$Pos"><% end_if %>
            <% if $hasField('MenuTitle') %>
                $MenuTitle.XML
            <% else %>
                $Title.XML
            <% end_if %>
            <% if not Up.Unlinked %></a><% end_if %> $Up.Delimiter.RAW
        <% end_if %>
    <% end_loop %>
<% end_if %>
