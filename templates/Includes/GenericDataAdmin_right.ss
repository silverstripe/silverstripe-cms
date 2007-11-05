<% include Editor_toolbar %>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h1>$ApplicationName</h1>

		<p>Welcome to $ApplicationName! Please choose click on one of the entries on the left pane.</p>
		
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
