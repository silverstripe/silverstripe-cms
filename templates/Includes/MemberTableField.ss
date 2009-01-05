<div id="$id" class="$CSSClasses" href="$CurrentLink">
	<div class="MemberFilter filterBox">
		$SearchForm
	</div>
	<div id="MemberList"> 
		<% include TableListField_PageControls %>
		<table class="data">
			<thead>
				<tr>
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<% control Headings %>
					<th class="$Name">$Title</th>
					<% end_control %>
					<% if Can(show) %><th width="18">&nbsp;</th><% end_if %>
					<% if Can(edit) %><th width="18">&nbsp;</th><% end_if %>
					<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
				</tr>
			</thead>
			<tfoot>
				<% if can(add) %>
				 <tr class="addtogrouprow">
					<% if Markable %><td width="18">&nbsp;</dh><% end_if %>
					$AddRecordForm.CellFields
					<td class="actions" colspan="3">$AddRecordForm.CellActions</td>
				</tr>
				<tr style="display: none;">
					<% if Markable %><td width="18">&nbsp;</td><% end_if %>
					<td colspan="$ItemCount">
						<a class="popuplink addlink" href="$AddLink" alt="add"><img src="cms/images/add.gif" alt="add" /></a><a class="popuplink addlink" href="$AddLink" alt="add"><% _t('ADDNEW','Add new',50,'Followed by a member type') %> $Title</a>
					</td>
					<% if Can(show) %><td width="18">&nbsp;</td><% end_if %>
					<% if Can(edit) %><td width="18">&nbsp;</td><% end_if %>
					<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
				</tr>
				<% end_if %>
			</tfoot>
			<tbody>
				<% if Items %>
				<% control Items %>
					<tr id="record-$Parent.Name-$ID">
						<% if Markable %><td width="18" class="markingcheckbox">$MarkingCheckbox</td><% end_if %>
						<% control Fields %>
						<td>$Value</td>
						<% end_control %>
						<% if Can(show) %>
						<td width="18" class="action">
							<a class="popuplink showlink" href="$ShowLink" title="<% _t('SHOWMEMBER','Show this member') %>" target="_blank"><img src="cms/images/show.png" alt="show" /></a>
						</td>
						<% end_if %>
						<% if Can(edit) %>
						<td width="18" class="action">
							<a class="popuplink editlink" href="$EditLink" title="<% _t('EDITMEMBER','Edit this member') %>" target="_blank"><img src="cms/images/edit.gif" alt="edit" /></a>
						</td>
						<% end_if %>
						<% if Can(delete) %>
						<td width="18" class="action">
							<a class="deletelink" href="$DeleteLink" title="<% _t('DELETEMEMBER','Delete this member') %>"><img src="cms/images/delete.gif" alt="delete" /></a>
						</td>
						<% end_if %>
					</tr>
				<% end_control %>
				<% else %>
				<tr class="notfound">
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<td colspan="$Headings.Count"><i>No $NamePlural found</i></td>
					<% if Can(show) %><td width="18">&nbsp;</td><% end_if %>
					<% if Can(edit) %><td width="18">&nbsp;</td><% end_if %>
					<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
				</tr>
			<% end_if %>
			</tbody>
		</table>
		<div class="utility">
			<% control Utility %>
				<span class="item"><a href="$Link" target="_blank">$Title</a></span>
			<% end_control %>
		</div>
	</div>
</div>