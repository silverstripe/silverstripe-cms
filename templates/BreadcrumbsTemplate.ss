<% if Pages %>
	<% control Pages %>
		<% if Last %>$Title.XML<% else %><a href="$Link">$MenuTitle.XML</a> &raquo;<% end_if %>
	<% end_control %>
<% end_if %>