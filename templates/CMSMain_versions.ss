<table id="Versions">
	<thead>
	<tr>
		<td>#</td>
		<td>When</td>
		<td>Author</td>
		<td>Publisher</td>
	</tr>
	</thead>
	<tbody>
	<% control Versions %>
	<tr id="page-$RecordID-version-$Version" class="$EvenOdd $PublishedClass">
		<td>$Version</td>
		<td class="$LastEdited" title="$LastEdited.Ago">$LastEdited.Nice</td>
		<td>$Author.FirstName $Author.Surname.Initial</td>
		<td>
		<% if Published %>
			<% if Publisher %>
				$Publisher.FirstName $Publisher.Surname.Initial
			<% else %>	
				Unknown
			<% end_if %>
		<% else %>
			Not published
		<% end_if %>
		</td>			
	</tr>
	<% end_control %>
	</tbody>
</table>