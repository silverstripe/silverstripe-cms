<div class="title"><div>Report Types</div></div>

<div id="treepanes">
<div id="sitetree_holder" style="overflow:auto">
	<% if Items %>
		<ul id="sitetree" class="tree unformatted">
		<li id="$ID" class="root Root"><a>Items</a>
			<ul>
			<% control Items %>
				<li id="record-$class">
				<a href="admin/statistics/show/$ID">$Title</a>
				</li>
			<% end_control %>
			</ul>
		</li>
		</ul>
	<% end_if %>
</div>

<h2 id="heading_versions">Page Version History</h2>
		<div class="listpane" id="versions_holder">
			<p class="pane_actions" id="versions_actions">
			</p>
			
			<div class="unitBody">
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
				<% control versions %>
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
			</div>
		</div>

</div>
