<% if Entries %>
<p><b>Instructions:</b></p>
<ul>
	<li>Uncheck the box to enable sending to a blacklisted email address.</li>
	<li>To remove a recipients's email address from your mailing list, click the <img src="cms/images/delete.gif" alt="delete" /> icon.</li>
</ul>
<table id="BouncedListTable" class="CMSList BouncedList" summary="Emails that have bounced">
  <thead>
    <tr>
	<th width="18">Blacklisted</th>
	<th>User name</th>
	<th>Email address</th>
	<th>Reason:</th>
	<th>Last bounce at</th>
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
<p>No emails sent have bounced.</p>
<% end_if %>