<% if SentRecipients(Failed) %>
	<h2 class="error" style="width:auto;">Sending to the Following Recipients Failed</h2>
	<table class="CMSList">
		<thead>
			<tr>
				<th class="Email" style="width:33%">Email</th>
				<th>Date</th>
				<th>Result</th>
			</tr>
		</thead>
		<tbody>
			<% control SentRecipients(Failed) %>
			<tr>
				<td>$Email</td>
				<td>$LastEdited</td>
				<td>$Result</td>
			</tr>
			<% end_control %>
			
		</tbody>
	</table>
<% end_if %>

<% if SentRecipients(Bounced) %>
	<h2 class="error" style="width:auto;">Sending to the Following Recipients Bounced</h2>

	<table class="CMSList">
		<thead>
			<tr>
				<th class="Email" style="width:33%">Email</th>
				<th>Date</th>
				<th>Result</th>
			</tr>
		</thead>
		
		<tbody>
			<% control SentRecipients(Bounced) %>
			<tr>
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
				<th class="Email" style="width:33%">Email</th>
				<th class="FirstName">Firstname</th>
				<th class="Surname">Surname</th>
			</tr>
		</thead>
		
		<tbody>
			<% control UnsentSubscribers %>
			<tr id="unsent-member-$ID">
				<td>$Email</td>
				<td>$FirstName</td>
				<td>$Surname</td>
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
				<th class="Email" style="width:33%">Email</th>
				<th>Date</th>
				<th>Result</th>
			</tr>
		</thead>
		
		<tbody>
			<% control SentRecipients(Sent) %>
			<tr id="sent-member-$ID">
				<td>$Email</td>
				<td>$LastEdited</td>
				<td>$Result</td>
			</tr>
			<% end_control %>
			
		</tbody>
	</table>
<% end_if %>
