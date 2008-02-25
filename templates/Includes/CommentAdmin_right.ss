<div id="form_actions_right" class="ajaxActions">
</div>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/comments?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<p><% _t('WELCOME1', 'Welcome to the') %> $ApplicationName <% _t('WELCOME2', 'comment management. Please select a folder in the tree on the left.') %></p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
