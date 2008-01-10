<% if SentRecipients(Failed) %>
	<h2 class="error" style="width:auto;"><% _t('SENDFAIL','Sending to the Following Recipients Failed') %></h2>
	<table class="CMSList">
		<thead>
			<tr>
				<th class="Email" style="width:33%"><% _t('EMAIL','Email') %></th>
				<th><% _t('DATE','Date') %></th>
				<th><% _t('RES','Result') %></th>
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
	<h2 class="error" style="width:auto;"><% _t('SENDBOUNCED','Sending to the Following Recipients Bounced') %></h2>

	<table class="CMSList">
		<thead>
			<tr>
				<th class="Email" style="width:33%"><% _t('EMAIL') %></th>
				<th><% _t('DATE') %></th>
				<th><% _t('RES') %></th>
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

<% if SentRecipients(BlackListed) %>
	<h2 class="error" style="width:auto;"><% _t('FAILEDBL', 'Sending to the Following Recipients Did Not Occur Because They Are BlackListed') %></h2>

	<table class="CMSList">
		<thead>
			<tr>
				<th class="Email" style="width:33%"><% _t('EMAIL') %></th>
				<th><% _t('DATE') %></th>
				<th><% _t('RES') %></th>
			</tr>
		</thead>
		
		<tbody>
			<% control SentRecipients(BlackListed) %>
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
	<h2><% _t('NEWSNEVERSENT','The Newsletter has Never Been Sent to Following Subscribers') %></h2>
	<table class="CMSList">
		<thead>
			<tr>
				<th class="Email" style="width:33%"><% _t('EMAIL') %></th>
				<th class="FirstName"><% _t('FN','Firstname') %></th>
				<th class="Surname"><% _t('SN','Surname') %></th>
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
	<h2><% _t('SENTOK','Sending to the Following Recipients was Successful') %></h2>
	<table class="CMSList">
		<thead>
			<tr>
				<th class="Email" style="width:33%"><% _t('EMAIL') %></th>
				<th><% _t('DATE') %></th>
				<th><% _t('RES') %></th>
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
