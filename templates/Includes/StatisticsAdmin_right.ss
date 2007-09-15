<!-- <div class="title"><div>Statistics</div></div> -->

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="" method="post" enctype="multipart/form-data">
		<p>Total users: $UserCount</p>
				
		
		<% if UserTableRecords %>
		<table class="sortable statstable" cellspacing="0">
			<thead>
				<tr><th class="nobg"></th><th>Email</th><th>Type</th></tr>
			</thead>
			<tbody>
				<% control UserTableRecords %>
				<tr><td class="id">$Iteration</td><td>$Email</td><td>$ClassName</td></tr>
				<% end_control %>
			</tbody>
		</table>
		<% else %>
		<p>No tabular data available!</p>
		<% end_if %>
		
		
	</form>
<% end_if %>



<p id="statusMessage" style="visibility:hidden"></p>
