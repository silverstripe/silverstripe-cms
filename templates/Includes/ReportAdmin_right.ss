<div class="title"><div>Report Details</div></div>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/reports?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<p>Welcome to the $ApplicationName reporting section.  Please choose a specific report from the left.</p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
