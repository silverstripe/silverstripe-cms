<div id="form_actions_right" class="ajaxActions">
</div>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/security?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<p><% _t('WELCOME1','Welcome to the',50,'Followed by application name') %> $ApplicationName <% _t('WELCOME2','security admininistration section.  Please choose a group from the left.',50) %></p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
