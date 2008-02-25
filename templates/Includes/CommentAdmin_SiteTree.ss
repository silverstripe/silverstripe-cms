<ul id="sitetree" class="tree unformatted">
	<li id="$ID" class="Root"><a><strong>Comments</strong></a>
		<ul>
			<li id="record-approved" <% if Section=approved %>class="current"<% end_if %>>
				<a href="$baseURL/admin/comments/showtable/approved" title="Approved">Approved</a>
			</li>
			<li id="record-unmoderated" <% if Section=unmoderated %>class="current"<% end_if %>>
				<a href="$baseURL/admin/comments/showtable/unmoderated" title="Awaiting Moderation">Awaiting Moderation</a>
			</li>
			<li id="record-spam">
				<a href="$baseURL/admin/comments/showtable/spam" title="Spam" <% if Section=spam %>class="current"<% end_if %>>Spam</a>
			</li>
		</ul>
	</li>
</ul>