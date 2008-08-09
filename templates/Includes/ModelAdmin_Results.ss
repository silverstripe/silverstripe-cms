<% if Results %>
	$Form
<% else %>
	<p><% sprintf(_t('GenericDataAdmin.NORESULTS'), $ModelPluralName) %></p>
<% end_if %>