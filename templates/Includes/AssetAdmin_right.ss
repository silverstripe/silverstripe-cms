<div id="form_actions_right" class="ajaxActions">
</div>

<!--
<form class="actionparams" id="action_movemarked_options" style="display:none" action="admin/assets/movemarked">
	<fieldset>
	<input type="hidden" name="ID" />
	<div class="field">
		<label class="left">Move files to</label>
		$ChooseFolderField
	</div>
	</fieldset>
	
	<p class="actions">
		<input type="submit" value="Move marked files" />
	</p>
</form>
-->

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/assets/?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h1>$ApplicationName</h1>

		<p><% _t('WELCOME','Welcome to') %> $ApplicationName! <% _t('CHOOSEPAGE','Please choose a page from the left.') %></p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
