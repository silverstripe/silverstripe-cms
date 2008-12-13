<ul id="sitetree" class="tree unformatted">
	<li id="$ID" class="Root"><a><strong><% _t('COMMENTS', 'Comments') %></strong></a>
		<ul>
			<li id="record-approved" <% if Section=approved %>class="current"<% end_if %>>
				<a href="$baseURL/admin/comments/showtable/approved" title="<% _t('CommentAdmin_SiteTree.ss.APPROVED', 'Approved') %>"><% _t('CommentAdmin_SiteTree.ss.APPROVED', 'Approved') %> ($NumModerated)</a>
			</li>
			<li id="record-unmoderated" <% if Section=unmoderated %>class="current"<% end_if %>>
				<a href="$baseURL/admin/comments/showtable/unmoderated" title="<% _t('CommentAdmin_SiteTree.ss.AWAITMODERATION', 'Awaiting Moderation') %>"><% _t('CommentAdmin_SiteTree.ss.AWAITMODERATION', 'Awaiting Moderation') %> ($NumUnmoderated)</a>
			</li>
			<li id="record-spam">
				<a href="$baseURL/admin/comments/showtable/spam" title="<% _t('CommentAdmin_SiteTree.ss.SPAM', 'Spam') %>" <% if Section=spam %>class="current"<% end_if %>><% _t('CommentAdmin_SiteTree.ss.SPAM', 'Spam') %> ($NumSpam)</a>
			</li>
		</ul>
	</li>
</ul>
