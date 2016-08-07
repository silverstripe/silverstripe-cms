<div id="$id" class="$CSSClasses field" href="$CurrentLink">
	<div class="FileFilter filterBox">
		$SearchForm
	</div>
	<% if Markable %>
		<% include TableListField_SelectOptions %>
	<% end_if %>
	<% include TableListField_PageControls %>
	<table class="data">
		<thead>
			<tr>
				<th width="18">&nbsp;</th>
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<% control Headings %>
				<th class="$Name">$Title</th>
				<% end_control %>
				<th width="18">&nbsp;</th>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
		</thead>
		<tbody>
			<% if Items %>
				<% control Items %>
					<tr id="record-$Parent.Name-$ID">
						<td class="dragfile" id="drag-$Parent.Name-$ID">
							<img id="drag-img-$Parent.Name-$ID" alt="Drag" title="<% _t('DRAGTOFOLDER','Drag to folder on left to move file') %>" src="sapphire/images/drag.gif" />
							<span class="linkCount" style="display: none;">$BackLinkTrackingCount</span>
						</td>
						<% if Markable %><td class="markingcheckbox">$MarkingCheckbox</td><% end_if %>
						<% control Fields %>
						<td>$Value</td>
						<% end_control %>
						<% if Can(show) %>
						<td width="18" class="action">
							<a class="popuplink showlink" href="$ShowLink" target="_blank" title="<% _t('SHOW', 'Show asset') %>"><img src="cms/images/show.png" alt="<% _t('SHOW', 'Show asset') %>" /></a>
						</td>
						<% end_if %>
						<% if Can(edit) %>
							<td width="18" class="action">
								<a class="popuplink editlink" href="$EditLink" target="_blank" title="<% _t('EDIT', 'Edit asset') %>"><img src="cms/images/edit.gif" alt="<% _t('EDIT', 'Edit asset') %>" /></a>
							</td>
						<% end_if %>
						<% if Can(delete) %>
						<td width="18" class="action">
							<a class="deletelink" href="admin/assets/removefile/$ID/?SecurityID=$SecurityID" title="<% _t('DELFILE', 'Delete this file') %>"><img src="cms/images/delete.gif" alt="<% _t('DELFILE', 'Delete this file') %>" title="<% _t('DELFILE','Delete this file') %>" /></a>
						</td>
						<% end_if %>
					</tr>
				<% end_control %>
			<% else %>
				<tr class="notfound"> 
					<td>&nbsp;</td> 
					<% if Markable %><td width="18">&nbsp;</td><% end_if %> 
					<td colspan="$Headings.Count"><i><% sprintf(_t('AssetTableField.NODATAFOUND', 'No %s found'),$NamePlural) %></i></td> 
					<% if Can(show) %><td width="18">&nbsp;</td><% end_if %> 
					<% if Can(edit) %><td width="18">&nbsp;</td><% end_if %> 
					<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %> 
				</tr> 
			<% end_if %>
		</tbody>
	</table>
</div>
