<table id="Versions">
	<thead>
	<tr>
		<td>#</td>
		<td><% _t('WHEN','When') %></td>
		<td><% _t('AUTHOR','User') %></td>
		<td><% _t('PUBR','Publisher') %></td>
	</tr>
	</thead>
	<tbody>
	<% control Versions %>
	<tr id="page-$RecordID-version-$Version" class="$EvenOdd $PublishedClass">
		<td>$Version</td>
		<td class="$LastEdited" title="$LastEdited.Ago - $LastEdited.Nice">$LastEdited.Ago</td>
		<td>$Author.FirstName $Author.Surname.Initial</td>
		<td>
		<% if Published %>
			<% if Publisher %>
				$Publisher.FirstName $Publisher.Surname.Initial
			<% else %>	
				<% _t('UNKNOWN','Unknown') %>
			<% end_if %>
		<% else %>
			<% _t('NOTPUB','Not published') %>
		<% end_if %>
		</td>			
	</tr>
	<% end_control %>
	</tbody>
</table>