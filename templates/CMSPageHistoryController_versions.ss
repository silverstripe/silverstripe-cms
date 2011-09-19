<table id="cms-page-history-versions">
	<thead>
		<tr>
			<th class="ui-helper-hidden"></th>
			<th><% _t('WHEN','When') %></th>
			<th><% _t('AUTHOR','Author') %></th>
			<th><% _t('PUBLISHER','Publisher') %></th>
		</tr>
	</thead>
	
	<tbody>
		<% control Versions %>
		<tr id="page-$RecordID-version-$Version" class="$EvenOdd $PublishedClass<% if not WasPublished %><% if not Active %> ui-helper-hidden<% end_if %><% end_if %><% if Active %> active<% end_if %>" data-published="<% if WasPublished %>true<% else %>false<% end_if %>">
			<td class="ui-helper-hidden"><input type="checkbox" name="Versions[]" id="cms-version-{$Version}" value="$Version"<% if Active %> checked="checked"<% end_if %> /></td>
			<% control LastEdited %>
				<td class="last-edited first-column" title="$Ago - $Nice">$Nice</td>
			<% end_control %>
			<td><% if Author %>$Author.FirstName $Author.Surname.Initial<% else %><% _t('UNKNOWN','Unknown') %><% end_if %></td>
			<td class="last-column"><% if Published %><% if Publisher %>$Publisher.FirstName $Publisher.Surname.Initial<% else %><% _t('UNKNOWN','Unknown') %><% end_if %><% else %><% _t('NOTPUBLISHED','Not published') %><% end_if %></td>			
		</tr>
		<% end_control %>
	</tbody>
</table>