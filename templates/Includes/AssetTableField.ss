<div id="$id" class="$Classes">
	<% include TableListField_PageControls %>
	<table class="data">
		<thead>
			<tr>
				<% if Markable %><th width="36">&nbsp;</th><% end_if %>
				<% control Headings %>
				<th class="$Name">$Title</th>
				<% end_control %>
				<th width="18">&nbsp;</th>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
		</thead>
		<tbody>
			<% control Items %>
				<tr id="record-$Parent.Name-$ID">
					<% if Markable %><td width="36" class="markingcheckbox"><div class="dragfile" id="drag-$Parent.Name-$ID"><img id="drag-img-$Parent.Name-$ID" alt="Drag" title="<% _t('DRAGTOFOLDER','Drag to folder on left to move file') %>" src="sapphire/images/drag.gif" /></div> $MarkingCheckbox</td><% end_if %>
					<% control Fields %>
					<td>$Value</td>
					<% end_control %>
					<% if Can(show) %>
                        <td width="18">
                            <a class="popuplink showlink" href="$ShowLink" target="_blank" title="Show asset"><img src="cms/images/show.png" alt="show" /></a>
                        </td>
                    <% end_if %>
					<% if Can(edit) %>
    					<td width="18">
    						<a class="popuplink editlink" href="$EditLink" target="_blank" title="Edit asset"><img src="cms/images/edit.gif" alt="edit" /></a>
    					</td>
			        <% end_if %>
			        <% if Can(delete) %>
					<td width="18">
						<a class="deletelink" href="admin/assets/removefile/$ID" title="Delete this file"><img src="cms/images/delete.gif" alt="delete" title="<% _t('DELFILE','Delete this file') %>" /></a>
					</td>
					<% end_if %>
				</tr>
			<% end_control %>
		</tbody>
	</table>
</div>
  <script type="text/javascript">
    new CheckBoxRange(document.getElementById('Form_EditForm'), 'Files[]');
  </script>
