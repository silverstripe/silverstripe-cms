<div id="$id" class="$Classes">
	<% include TableListField_PageControls %>
	<table class="data">
		<thead>
			<tr>
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<% control Headings %>
				<th class="$Name">$Title</th>
				<% end_control %>
				<th width="18">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<% control Items %>
				<tr id="record-$Parent.Name-$ID">
					<% if Markable %><td width="18" class="markingcheckbox">$MarkingCheckbox</td><% end_if %>
					<% control Fields %>
					<td>$Value</td>
					<% end_control %>
					<td width="18">
						<a class="popuplink editlink" href="$EditLink" target="_blank" title="Edit asset"><img src="cms/images/edit.gif" alt="edit" /></a>
					</td>
				</tr>
			<% end_control %>
		</tbody>
	</table>
</div>