<% if $UnsuccessfulFiles %>
	<p style=\"margin: 1em 0\">
		<%t SilverStripe\CMS\Controllers\ContentController.UnableDeleteInstall "Unable to delete installation files. Please delete the files below manually" %>:
	</p>

	<ul>
		<% loop $UnsuccessfulFiles %>
		<li>$File</li>
		<% end_loop %>
	</ul>
<% else %>
	<p style="margin: 1em 0">
		<%t SilverStripe\CMS\Controllers\ContentController.InstallFilesDeleted "Installation files have been successfully deleted." %>
	</p>
	<p style="margin: 1em 0">
		<%t SilverStripe\CMS\Controllers\ContentController.StartEditing 'You can start editing your content by opening <a href="{link}">the CMS</a>.' link="admin/" %>
		<br />
		&nbsp; &nbsp; <%t SilverStripe\CMS\Controllers\ContentController.Email "Email" %>: $Username<br />
		&nbsp; &nbsp; <%t SilverStripe\CMS\Controllers\ContentController.Password "Password" %>: $Password<br />
	</p>
<% end_if %>
