<div class="title"><div style="background-image : url(cms/images/panels/EditPage.png)">Edit Page</div></div>
<% include Editor_toolbar %>

<div id="form_actions_right" class="ajaxActions">
</div>

<form class="actionparams" id="action_submit_options" style="display:none" action="admin/submit">
	<fieldset>
	<input type="hidden" name="ID" />
	<div class="field" id="action_submit_options_recipient">
		<label class="left">Send to</label>
		<span>loading...</span>
	</div>
	<div class="field" id="action_submit_options_status">
		<label class="left">Status</label>
		<input type="hidden" name="Status" />
		<span></span>
	</div>
	<p class="label">Do you have any message for your editor?</p>
	<textarea name="Message" rows="4" cols="20"></textarea>
	</fieldset>
	
	<p class="actions">
		<input type="submit" value="Submit for approval" />
	</p>
</form>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h1>$ApplicationName</h1>

		<p>Welcome to $ApplicationName! Please choose a page from the left.</p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
