<h1>Edit Content</h1>

<div class="mceToolbar_background">&nbsp;</div>

<p id="statusMessage"></p>

<div id="form_actions">
</div>

<% if EditForm %>
	$EditForm
<% else %>
	<h1>$ApplicationName</h1>
	<div class="mceToolbar_background">&nbsp;</div>
	<p id="statusMessage" style="display:none"></p>

	<form id="Form_EditForm" action="admin?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<p>Welcome to $ApplicationName! Please choose a page from the left.</p>
	</form>
<% end_if %>
</body>