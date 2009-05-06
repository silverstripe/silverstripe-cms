
<div id="ModelAdminPanel">

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h1>$ApplicationName</h1>

		<p><% sprintf(_t('WELCOME1','Welcome to %s. Please choose on one of the entries in the left pane.'),$ApplicationName) %></p>
		
	</form>
<% end_if %>

</div>

<p id="statusMessage" style="visibility:hidden"></p>
