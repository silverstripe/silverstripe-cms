<div id="Logo" style="$LogoStyle">
	<% if ApplicationLogoText %>
	<a href="$ApplicationLink">$ApplicationLogoText</a><br />
	<% end_if %>
</div>
<ul id="MainMenu">
<% control MainMenu %>
	<li class="$LinkingMode" id="Menu-$Code"><a href="$Link">$Title</a></li>
<% end_control %>
</ul>
