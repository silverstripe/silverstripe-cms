<p>
	<%t ContentController.InstallSuccessCongratulations "SilverStripe has been successfully installed!" %>
</p>

<% if $Project == 'tutorial' %>
	<%t ContentController.PostInstallTutorialIntro 'This website is a simplistic version of a SilverStripe 3 site. To extend this, please take a look at {link}.' link='<a href="http://doc.silverstripe.org/framework/en/tutorials">our tutorials</a>' %>
<% end_if %>

	<p><strong>&nbsp; &nbsp; <%t ContentController.Email "Email" %>: $Username</strong></br>
	<strong>&nbsp; &nbsp; <%t ContentController.Password "Password" %>: $Password</strong></p>

<p>
	<%t ContentController.StartEditing 'You can start editing your content by opening <a href="{link}">the CMS</a>.' link="admin/" %>
</p>

<div style="background:#fcf8f2; border-radius:4px; border: 1px solid #ffc28b; padding:5px; margin:5px;">
	<img src="cms/images/dialogs/alert.gif" style="border: none; margin-right: 10px; float: left; height:48px; width:48px" />
	<p style="color: #cb6a1c; margin-bottom:0;">
	<%t ContentController.InstallSecurityWarning 'For security reasons you should now delete the install files, unless you are planning to reinstall later (<em>requires admin login, see above</em>). The web server also now only needs write access to the "assets" folder, you can remove write access from all other folders. <a href="{link}" style="text-align: center;">Click here to delete the install files.</a>' link="home/deleteinstallfiles" %>
	</p>
</div>
