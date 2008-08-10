
<div id="ModelAdminPanel" style="display: auto">

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h1>$ApplicationName</h1>

		<p><% _t('WELCOME1', 'Welcome to') %> $ApplicationName! <% _t('WELCOME2', 'Please choose on one of the entries in the left pane.') %></p>
		
	</form>
<% end_if %>

</div>

<p id="statusMessage" style="visibility:hidden"></p>
