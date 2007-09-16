<% if Entries %>
<table class="CMSList UnsubscribedList" summary="Unsubscribed users">
    <tbody>
        <tr>
            <td><% _t('UNAME','User name') %></td>
            <td><% _t('UNSUBON','Unsubscribed on') %></td>
        </tr>
        <% control Entries %>
        <tr>
            <td>$Member.FirstName $Member.Surname</td>
            <td>$Record.Created.Long</td>
        </tr>
        <% end_control %>
    </tbody>
</table>
<% else %>
<p>
    <% _t('NOUNSUB','No users have unsubscribed from this newsletter.') %>

</p>
<% end_if %>
