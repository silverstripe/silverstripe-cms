<p>$Message</p>

<ul id="TaskList">
	<% control Tasks %>
	<li class="$EvenOdd">
		<a href="admin/show/$ID">$Title</a>
		<div class="extra"><% _t('BY','by') %> $RequestedBy.FirstName&nbsp;$RequestedBy.Surname.Initial, $Created.Ago</div>
	</li>
	<% end_control %>
</ul>