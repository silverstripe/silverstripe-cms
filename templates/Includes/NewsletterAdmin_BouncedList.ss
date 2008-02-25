<% if Entries %>
<p><b><% _t('INSTRUCTIONS', 'Instructions:') %></b></p>
<ul>
	<li><% _t('INSTRUCTIONS1', 'Uncheck the box to enable sending to a blacklisted email address.') %></li>
	<li><% _t('INSTRUCTIONS2', 'To remove a recipients\'s email address from your mailing list, click the icon') %> <img src="cms/images/delete.gif" alt="delete" /></li>
</ul>
<table id="BouncedListTable" class="CMSList BouncedList" summary="<% _t('HAVEBOUNCED','Emails that have bounced') %>">
  <thead>
    <tr>
	<th width="18"><% _t('BLACKLISTED', 'Blacklisted') %></th>
	<th><% _t('UNAME','User name') %></th>
	<th><% _t('EMADD','Email address') %></th>
	<th><% _t('RESON', 'Reason:') %></th>
	<th><% _t('DATE', 'Date') %></th>
	<th width="18">&nbsp;</th>
    </tr>
  </thead>
  <tbody>
    <% control Entries %>
    <tr>
	<td class="markingcheckbox">
		<% if Member.BlacklistedEmail  %>
			<input class="checkbox" type="checkbox" checked="checked" name="BouncedList[]" value="$Record.ID" />
		<% else %>
			<input class="checkbox" type="checkbox" name="BouncedList[]" value="$Record.ID" />
		<% end_if %>
	</td>
      <td>$Member.FirstName $Member.Surname</td>
      <td>$Member.Email</td>
	  <td>$Record.BounceMessage</td>
      <td>$Record.Created.Long</td>
<td width="16"><a class="deletelink" href="admin/newsletter/removebouncedmember/$Record.ID/?GroupID=$GroupID"><img src="cms/images/delete.gif" alt="delete" /></a></td>
    </tr>
    <% end_control %>
  </tbody>
</table>
<% else %>
<p><% _t('NOBOUNCED','No emails sent have bounced.') %></p>
<% end_if %>
