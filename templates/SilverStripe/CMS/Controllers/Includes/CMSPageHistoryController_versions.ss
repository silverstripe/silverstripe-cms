<table id="cms-page-history-versions">
	<thead>
		<tr>
			<th class="ui-helper-hidden"></th>
			<th class="first-column"><%t SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.WHEN 'When' %></th>
			<th><%t SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.AUTHOR 'Author' %></th>
			<th class="last-column"><%t SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.PUBLISHER 'Publisher' %></th>
		</tr>
	</thead>

	<tbody>
		<% loop $Versions %>
		<tr id="page-$RecordID-version-$Version" class="$EvenOdd $PublishedClass<% if not $Published %><% if not $Active %> ui-helper-hidden<% end_if %><% end_if %><% if $Active %> active<% end_if %>" data-published="<% if $Published %>true<% else %>false<% end_if %>">
			<td class="ui-helper-hidden"><input type="checkbox" name="Versions[]" id="cms-version-{$Version}" value="$Version"<% if $Active %> checked="checked"<% end_if %> /></td>
			<% with $LastEdited %>
				<td class="last-edited first-column" title="$Ago - $Nice">$Nice</td>
			<% end_with %>
			<td><% if $Author %>$Author.FirstName $Author.Surname.Initial<% else %><%t SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.UNKNOWN 'Unknown' %><% end_if %></td>
			<td class="last-column"><% if $Published %><% if $Publisher %>$Publisher.FirstName $Publisher.Surname.Initial<% else %><%t SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.UNKNOWN 'Unknown' %><% end_if %><% else %><%t SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.NOTPUBLISHED 'Not published' %><% end_if %></td>
		</tr>
		<% end_loop %>
	</tbody>
</table>
