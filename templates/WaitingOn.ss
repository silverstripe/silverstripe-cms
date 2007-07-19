<p>$Message</p>

<ul id="TaskList">
	<% control Tasks %>
	<li class="$EvenOdd">
		<a href="admin/show/$ID">$Title</a>
		<div class="extra">assigned to $AssignedTo.FirstName&nbsp;$AssignedTo.Surname.Initial $LastEdited.Ago</div>
	</li>
	<% end_control %>
</ul>