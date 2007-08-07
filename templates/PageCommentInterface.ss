<div id="PageComments_holder" class="typography">
	<h4>Post your comment</h4>
	
	$PostCommentForm
	
	<h4>Comments</h4>
	
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
					<% if Comments.PrevLink %>
						<a href="$Comments.PrevLink">&laquo; previous</a>
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
						<a href="$Comments.NextLink">next &raquo;</a>
					<% end_if %>
				</div>
			<% end_if %>
		<% else %>
			<p id="NoComments">No one has commented on this page yet.</p>
		<% end_if %>
	</div>
	<a class="commentrss" href="$CommentRssLink">RSS feed for comments on this page</a>
</div>
	
