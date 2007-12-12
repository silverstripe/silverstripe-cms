<div id="PageComments_holder" class="typography">
	<h4><% _t('POSTCOM','Post your comment') %></h4>
	
	$PostCommentForm
	
	<h4><% _t('COMMENTS','Comments') %></h4>
	
	<div id="CommentHolder">
		<% if Comments %>
			<ul id="PageComments">
				<% control Comments %>
					<li class="$EvenOdd<% if FirstLast %> $FirstLast <% end_if %> $SpamClass">
						<% include PageCommentInterface_singlecomment %>
					</li>
				<% end_control %>
			</ul>
			
			<% if Comments.MoreThanOnePage %>
				<div id="PageCommentsPagination">
					<p>
					<% if Comments.PrevLink %>
						<a href="$Comments.PrevLink">&laquo; <% _t('PREV','previous') %></a>
					<% end_if %>
					
					<% if Comments.Pages %>
						<% control Comments.Pages %>
							<% if CurrentBool %>
								<strong>$PageNum</strong>
							<% else %>
								<a href="$Link">$PageNum</a>
							<% end_if %>
						<% end_control %>
					<% end_if %>
	
					<% if Comments.NextLink %>
						<a href="$Comments.NextLink"><% _t('NEXT','next') %> &raquo;</a>
					<% end_if %>
					</p>
				</div>
			<% end_if %>
		<% else %>
			<p id="NoComments"><% _t('NOCOMMENTSYET','No one has commented on this page yet.') %></p>
		<% end_if %>
	</div>
	<p id="CommentsRSSFeed"><a class="commentrss" href="$CommentRssLink"><% _t('RSSFEEDCOMMENTS', 'RSS feed for comments on this page') %></a></p>
</div>
	
