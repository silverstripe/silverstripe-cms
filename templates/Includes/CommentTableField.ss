<div id="$id" class="$Classes">
	<% include TableListField_PageControls %>
	<table class="data">
		<thead>
			<tr>
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<% control Headings %>
				<th class="$Name">
					<% if IsSortable %>
						<span class="sortTitle">
							<a href="$SortLink">$Title</a>
						</span>
						<span class="sortLink <% if SortBy %><% else %>sortLinkHidden<% end_if %>">
							<a href="$SortLink"">
								<% if SortDirection = desc %>
								<img src="cms/images/bullet_arrow_up.png" alt="Sort ascending" />
								<% else %>
								<img src="cms/images/bullet_arrow_down.png" alt="Sort descending" />
								<% end_if %>
							</a>
							&nbsp;
						</span>
					<% else %>
						$Title
					<% end_if %>
				</th>
				<% end_control %>
				<% if Can(edit) %><th width="18">&nbsp;</th><% end_if %>
				<% if HasAcceptButton %><th width="18">&nbsp;</th><% end_if %>
				<% if HasSpamButton %><th width="18">&nbsp;</th><% end_if %>
				<% if HasHamButton %><th width="18">&nbsp;</th><% end_if %>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
		</thead>
		<tfoot>
			<% if HasSummary %>
			<tr class="summary">
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<td><i>$SummaryTitle</i></td>
				<% control SummaryFields %>
					<td<% if Function %> class="$Function"<% end_if %>>&nbsp;</td>
				<% end_control %>
				<% if Can(edit) %><th width="18">&nbsp;</th><% end_if %>
				<% if HasAcceptButton %><th width="18">&nbsp;</th><% end_if %>
				<% if HasSpamButton %><th width="18">&nbsp;</th><% end_if %>
				<% if HasHamButtom %><th width="18">&nbsp;</th><% end_if %>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
			<% end_if %>
		</tfoot>
		<tbody>
			<% if Items %>
			<% control Items %>
				<tr id="record-$Parent.id-$ID"<% if HighlightClasses %> class="$HighlightClasses"<% end_if %>>
					<% if Markable %><td width="18" class="markingcheckbox">$MarkingCheckbox</td><% end_if %>
					<% control Fields %>
					<td>$Value</td>
					<% end_control %>
					<% if Can(edit) %>
						<td width="18"><a class="popuplink editlink" href="$EditLink" target="_blank"><img src="cms/images/edit.gif" alt="edit" /></a></td>
					<% end_if %>
					<% if HasAcceptButton %>
						<td width="18"><a class="acceptlink" href="$AcceptLink" title="Accept this comment"><img src="cms/images/accept.gif" alt="accept" /></a></td>
					<% end_if %>
					<% if HasSpamButton %>
						<td width="18"><a class="spamlink" href="$SpamLink" title="Mark this comment as spam"><img src="cms/images/spam.gif" alt="spam" /></a></td>
					<% end_if %>
					<% if HasHamButton %>
						<td width="18"><a class="hamlink" href="$HamLink" title="Mark this comment as not spam"><img src="cms/images/ham.gif" alt="ham" /></a></td>
					<% end_if %>
					<% if Can(delete) %>
						<td width="18"><a class="deletelink" href="$DeleteLink" title="Delete this row"><img src="cms/images/delete.gif" alt="delete" /></a></td>
					<% end_if %>
				</tr>
			<% end_control %>
			<% else %>
				<tr class="notfound">
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<td colspan="$Headings.Count"><i>No items found</i></td>
					<% if Can(edit) %><th width="18">&nbsp;</th><% end_if %>
					<% if HasAcceptButton %><th width="18">&nbsp;</th><% end_if %>
					<% if HasSpamButton %><th width="18">&nbsp;</th><% end_if %>
					<% if HasHamButtom %><th width="18">&nbsp;</th><% end_if %>
					<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
				</tr>
			<% end_if %>
		</tbody>
	</table>
</div>