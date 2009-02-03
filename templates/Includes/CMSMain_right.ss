<div id="form_actions_right" class="ajaxActions">
</div>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h1>$ApplicationName</h1>

		<p><% _t('WELCOMETO','Welcome to') %> $ApplicationName! <% _t('CHOOSEPAGE','Please choose a page from the left.') %></p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
