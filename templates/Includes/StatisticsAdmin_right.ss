<form id="Form_EditForm" action="" method="post" enctype="multipart/form-data">
<% if EditForm %>
	$EditForm
<% else %>

<p>Welcome to the $ApplicationName newsletter admininistration section.  Please choose a folder from the left.</p>

<% end_if %>
</form>


<p id="statusMessage" style="visibility:hidden"></p>
