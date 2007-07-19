<table id="{$Status}List" class="CMSList NewsletterList">
	<thead>
		<tr>
			<td class="Email">Subject</td>
			<td class="Surname">Content</td>
		</tr>
	</thead>
	
	<tbody>
		<% control Newsletters %>
		<tr id="letter-$ID">
			<td>$Subject</td>
			<td>$Content</td>
		</tr>
		<% end_control %>
		
		<!-- $AddRecordForm.AsTableRow -->
	</tbody>
</table>
