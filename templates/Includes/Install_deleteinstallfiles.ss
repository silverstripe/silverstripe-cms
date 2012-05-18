<% if UnsuccessfulFiles %>
	<p style=\"margin: 1em 0\">
		<%t ContentController.UnableDeleteInstall "Unable to delete installation files. Please delete the files below manually" %>:
	</p>

	<ul>
		<% loop UnsuccessfulFiles %>
		<li>$File</li>
		<% end_loop %>
	</ul>
<% else %>
	<p style="margin: 1em 0">
		<%t ContentController.InstallFilesDeleted "Installation files have been successfully deleted." %>
	</p>
	<p style="margin: 1em 0">
		<%t ContentController.StartEditing 'You can start editing your site\'s content by opening <a href="{link}">the CMS</a>.' link="admin/" %>. 
		<br />
		&nbsp; &nbsp; <%t ContentController.Email "Email" %>: $Username<br />
		&nbsp; &nbsp; <%t ContentController.Password "Password" %>: $Password<br />
	</p>
<% end_if %>