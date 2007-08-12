<p class="comment" id="PageComment_$ID">
	<% if NeedsModeration %>
		<p><b>Unmoderated comment</b></p>
	<% end_if %>
	$Comment.XML
</p>
<p class="info">
	<span>Posted by $Name.XML, $Created.Nice ($Created.Ago)</span>
	<br />
	<ul class="actionLinks">
		<% if ApproveLink %>
			<li><a href="$ApproveLink" class="approvelink">approve this comment</a></li>
		<% end_if %>
		<% if SpamLink %>
			<li><a href="$SpamLink" class="spamlink">this comment is spam</a></li>
		<% end_if %>
		<% if HamLink %>
			<li><a href="$HamLink" class="hamlink">this comment is not spam</a></li>
		<% end_if %>
		<% if DeleteLink %>
			<li class="last"><a href="$DeleteLink" class="deletelink">remove this comment</a></li>
		<% end_if %>
	</ul>
</p>
