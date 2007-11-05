<% include Editor_toolbar %>

<span id="Translating_Message" class="translatingMessage <% if EditingLang %><% else %>nonTranslating<% end_if %>"></span>

<div id="form_actions_right" class="ajaxActions">
</div>

<form class="actionparams" id="action_submit_options" style="display:none" action="admin/submit">
	<fieldset>
	<input type="hidden" name="ID" />
	<div class="field" id="action_submit_options_recipient">
		<label class="left"><% _t('SENDTO','Send to') %></label>
		<span><% _t('LOADING','loading...') %></span>
	</div>
	<div class="field" id="action_submit_options_status">
		<label class="left"><% _t('STATUS','Status') %></label>
		<input type="hidden" name="Status" />
		<span></span>
	</div>
	<p class="label"><% _t('ANYMESSAGE','Do you have any message for your editor?') %></p>
	<textarea name="<% _t('MESSAGE','Message') %>" rows="4" cols="20"></textarea>
	</fieldset>
	
	<p class="actions">
		<input type="submit" value="<% _t('SUBMIT','Submit for approval') %>" />
	</p>
</form>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<h1>$ApplicationName</h1>

		<p><% _t('WELCOMETO','Welcome to') %> $ApplicationName! <% _t('CHOOSEPAGE','Please choose a page from the left.') %></p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
