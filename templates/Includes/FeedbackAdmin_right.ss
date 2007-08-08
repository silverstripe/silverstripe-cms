<div class="title"><div>Feedback Management</div></div>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/feedback?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<p>Welcome to the $ApplicationName feedback management. Please select a folder in the tree on the left.</p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
