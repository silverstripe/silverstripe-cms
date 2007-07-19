<% if Entries %>
<table class="CMSList BouncedList" summary="Emails that have bounced">
  <thead>
    <tr>
      <th>User name</th>
      <th>Email address</th>
	  <th>Reason:</th>
      <th>Last bounce at</th>
    </tr>
  </thead>
  <tbody>
    <% control Entries %>
    <tr>
      <td>$Member.FirstName $Member.Surname</td>
      <td>$Member.Email</td>
	  <td>$Record.BounceMessage</td>
      <td>$Record.Created.Long</td>
    </tr>
    <% end_control %>
  </tbody>
</table>
<% else %>
<p>No emails sent have bounced.</p>
<% end_if %>
