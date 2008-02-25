<ul id="sitetree" class="tree unformatted">
	<li id="$ID" class="Root"><a><strong><% _t('COMMENTS', 'Comments') %></strong></a>
		<ul>
			<li id="record-approved" <% if Section=approved %>class="current"<% end_if %>>
				<a href="$baseURL/admin/comments/showtable/approved" title="<% _t('APPROVED', 'Approved') %>"><% _t('APPROVED', 'Approved') %></a>
			</li>
			<li id="record-unmoderated" <% if Section=unmoderated %>class="current"<% end_if %>>
				<a href="$baseURL/admin/comments/showtable/unmoderated" title="<% _t('AWAITMODERATION', 'Awaiting Moderation') %>"><% _t('AWAITMODERATION', 'Awaiting Moderation') %></a>
			</li>
			<li id="record-spam">
				<a href="$baseURL/admin/comments/showtable/spam" title="<% _t('SPAM', 'Spam') %>" <% if Section=spam %>class="current"<% end_if %>><% _t('SPAM', 'Spam') %></a>
			</li>
		</ul>
	</li>
</ul>
