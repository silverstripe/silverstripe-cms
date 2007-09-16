<% include MemberList_PageControls %>
<table class="CMSList MemberList">
	<thead>
		<tr>
		<td class="FirstName"><% _t('FN','First Name') %></td>
		<td class="Surname"><% _t('SN','Surname') %></td>
		<td class="Email"><% _t('EMAIL','Email Address') %></td>
	<% if DontShowPassword %>
	<% else %>
		<td class="Password"><% _t('PASSWD','Password') %></td>
	<% end_if %>
		<td>&nbsp;</td>
		</tr>
	</thead>
	
	<tbody>
	
	<% if DontShowPassword %>
		<% control Members %>
		<tr id="member-$ID">
		<td>$FirstName</td>
		<td>$Surname</td>
		<td>$Email</td>
		<td><a class="deletelink" href="admin/security/removememberfromgroup/$GroupID/$ID"><img src="cms/images/delete.gif" alt="delete" /></a></td>
		</tr>
		<% end_control %>
	<% else %>
		<% control Members %>
		<tr id="member-$ID">
		<td>$FirstName</td>
		<td>$Surname</td>
		<td>$Email</td>
		<td>$Password</td>
		<td><a class="deletelink" href="admin/security/removememberfromgroup/$GroupID/$ID"><img src="cms/images/delete.gif" alt="delete" /></a></td>
		</tr>
		<% end_control %>
	<% end_if %>
	
		
		$AddRecordForm.AsTableRow
	</tbody>
</table>