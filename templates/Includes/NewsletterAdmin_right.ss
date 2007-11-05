<% include Editor_toolbar %>

<div id="form_actions_right" class="ajaxActions">
</div>

<form class="actionparams" id="action_send_options" style="display:none" action="admin/newsletter/sendnewsletter">
	<fieldset>
	<input type="hidden" name="NewsletterID" />
	<ul class="optionset">
		<li><input name="SendType" id="SendTypeTest" value="Test" checked="checked" type="radio" /> <label for="SendTypeTest"><% _t('SENDTEST','Send test to') %></label> <input name="TestEmail" type="text" /></li>
		<li><input name="SendType" id="SendTypeList" value="List" type="radio" /> <label for="SendTypeList"><% _t('ENTIRE','Send to the entire mailing list') %></label></li>
		<li><input name="SendType" id="SendTypeUnsent" value="Unsent" type="radio" /> <label for="SendTypeUnsent"><% _t('ONLYNOT','Send to only people not previously sent to') %></label></li>
	</ul>
	$SendProgressBar
	</fieldset>
	
	<p class="actions">
		<ul class="optionset">
			<li class="cancel"><button id="action_send_cancel"><% _t('CANCEL','Cancel') %></button></li>
			<li class="submit"><input type="submit" value="<% _t('SEND','Send newsletter') %>" /></li>
		</ul>
	</p>
</form>

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin/newsletter?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<p><% _t('WELCOME1','Welcome to the',50,'Followed by application name') %> $ApplicationName <% _t('WELCOME2','newsletter admininistration section.  Please choose a folder from the left.',50) %></p>
	</form>
<% end_if %>

<p id="statusMessage" style="visibility:hidden"></p>
