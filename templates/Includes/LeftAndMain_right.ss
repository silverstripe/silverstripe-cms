<div class='ss-cmsForm-welcomeMessage'>
	<h1>$ApplicationName</h1>
	<p>
		<% _t('WELCOMETO','Welcome to') %> $ApplicationName! 
		<% _t('CHOOSEPAGE','Please choose a page from the left.') %>
	</p>
</div>
<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/EditForm" method="post" enctype="multipart/form-data">
	</form>
<% end_if %>

<div id="statusMessage"></div>