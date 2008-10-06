<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/reports/EditForm" method="post" enctype="multipart/form-data">
		<p><% _t('WELCOME1','Welcome to the',50,'Followed by application name') %> $ApplicationName <% _t('WELCOME2','reporting section.  Please choose a specific report from the left.',50) %></p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
