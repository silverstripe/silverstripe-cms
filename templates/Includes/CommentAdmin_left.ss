<div id="treepanes">
	<h3>
		<a href="#"><% _t('COMMENTS', 'Comments') %></a>
	</h3>

	<div id="sitetree_holder">
		<ul id="sitetree" class="tree unformatted">
			<li id="$ID" class="Root"><a><strong><% _t('COMMENTS', 'Comments') %></strong></a>
				<ul>
					<li id="record-approved" <% if Section=approved %>class="current"<% end_if %>>
						<a href="{$Link}showtable/approved" title="<% _t('CommentAdmin_SiteTree.ss.APPROVED', 'Approved') %>"><% _t('CommentAdmin_SiteTree.ss.APPROVED', 'Approved') %> ($NumModerated)</a>
					</li>
					<li id="record-unmoderated" <% if Section=unmoderated %>class="current"<% end_if %>>
						<a href="{$Link}showtable/unmoderated" title="<% _t('CommentAdmin_SiteTree.ss.AWAITMODERATION', 'Awaiting Moderation') %>"><% _t('CommentAdmin_SiteTree.ss.AWAITMODERATION', 'Awaiting Moderation') %> ($NumUnmoderated)</a>
					</li>
					<li id="record-spam">
						<a href="{$Link}showtable/spam" title="<% _t('CommentAdmin_SiteTree.ss.SPAM', 'Spam') %>" <% if Section=spam %>class="current"<% end_if %>><% _t('CommentAdmin_SiteTree.ss.SPAM', 'Spam') %> ($NumSpam)</a>
					</li>
				</ul>
			</li>
		</ul>
		
	</div>

</div>
