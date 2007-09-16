<% if SentRecipients(Failed) %>
	<h2 class="error">Sending to the Following Recipients Failed</h2>
	<table class="CMSList">
		<thead>
			<tr>
				<th class="FirstName">Firstname</th>
				<th class="Surname">Surname</th>
				<th class="Email">Email</th>
				<th>Date</th>
				<th>Result</th>
			</tr>
		</thead>
		<tbody>
			<% control SentRecipients(Failed) %>
			<tr>
				<td>$FirstName</td>
				<td>$Surname</td>
				<td>$Email</td>
				<td>$LastEdited</td>
				<td>$Result</td>
			</tr>
			<% end_control %>
			
		</tbody>
	</table>
<% end_if %>

@TODO: Make bounces actually show up here.
<% if SentRecipients(Bounced) %>
	<h2 class="error">Sending to the Following Recipients Bounced</h2>

	<table class="CMSList">
		<thead>
			<tr>
				<th class="FirstName">Firstname</th>
				<th class="Surname">Surname</th>
				<th class="Email">Email</th>
				<th>Date</th>
				<th>Result</th>
			</tr>
		</thead>
		
		<tbody>
			<% control SentRecipients(Bounced) %>
			<tr>
				<td>$FirstName</td>
				<td>$Surname</td>
				<td>$Email</td>
				<td>$LastEdited</td>
				<td>$Result</td>
			</tr>
			<% end_control %>
			
		</tbody>
	</table>
<% end_if %>

<% if UnsentSubscribers %>
	<h2>The Newsletter has Never Been Sent to Following Subscribers</h2>
	<table class="CMSList">
		<thead>
			<tr>
				<th class="FirstName">Firstname</th>
				<th class="Surname">Surname</th>
				<th class="Email">Email</th>
	
			</tr>
		</thead>
		
		<tbody>
			<% control UnsentSubscribers %>
			<tr id="unsent-member-$ID">
				<td>$FirstName</td>
				<td>$Surname</td>
				<td>$Email</td>
			</tr>
			<% end_control %>
			
		</tbody>
	</table>
<% end_if %>

<% if SentRecipients(Sent) %>
	<h2>Sending to the Following Recipients was Successful</h2>
	<table class="CMSList">
		<thead>
			<tr>
				<th class="FirstName">Firstname</th>
				<th class="Surname">Surname</th>
				<th class="Email">Email</th>
				<th>Date</th>
				<th>Result</th>
			</tr>
		</thead>
		
		<tbody>
			<% control SentRecipients(Sent) %>
			<tr id="sent-member-$ID">
				<td>$FirstName</td>
				<td>$Surname</td>
				<td>$Email</td>
				<td>$LastEdited</td>
				<td>$Result</td>
			</tr>
			<% end_control %>
			
		</tbody>
	</table>
<% end_if %>
