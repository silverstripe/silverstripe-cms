<div id="$id" class="$Classes TableField">
	<% include TableListField_PageControls %>
	<table class="data BouncedList">
		<thead>
			<tr>
			<% if Markable %><th width="16">&nbsp;</th><% end_if %>
			<% control Headings %>
			<th class="$Name">$Title</th>
			<% end_control %>
			<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
		</thead>
		
		<% if HasSummary %>
		<tfoot>
			<tr class="summary">
				<% if Markable %><th width="16">&nbsp;</th><% end_if %>
				<td><i>$SummaryTitle</i></td>
				<% control SummaryFields %>
					<td<% if Function %> class="$Function"<% end_if %>>&nbsp;</td>
				<% end_control %>
				<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
			</tr>
		</tfoot>
		<% end_if %>
		
		<tbody>
			<% if Items %>
			<% control Items %>
				<tr id="record-$ID"<% if HighlightClasses %> class="$HighlightClasses"<% end_if %>>
					<% if Markable %><td width="16">$MarkingCheckbox</td><% end_if %>
					<% control Fields %>
					<td>$Value</td>
					<% end_control %>
					<% if Can(delete) %>
						<td width="16"><a class="deletelink" href="admin/newsletter/removebouncedmember/$ID/?GroupID=$GroupID"><img src="cms/images/delete.gif" alt="delete" /></a></td>
					<% end_if %>
				</tr>
			<% end_control %>
			<% else %>
					<tr class="notfound">
						<% if Markable %><th width="18">&nbsp;</th><% end_if %>
						<td colspan="$Headings.Count"><i>No bounce records found</i></td>
						<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
					</tr>
				<% end_if %>
			<% if Can(add) %>$AddRecordForm.AsTableRow<% end_if %>
		</tbody>
	</table>
	<div class="utility">
		<% if Can(export) %>
			$ExportButton
		<% end_if %>
	</div>
</div>