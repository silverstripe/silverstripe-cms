<p class="comment" id="PageComment_$ID">
	<% if NeedsModeration %>
		<p><b>Unmoderated comment</b></p>
	<% end_if %>
	$Comment.XML
</p>
<p class="info">
	<span>Posted by $Name.XML, $Created.Nice ($Created.Ago)</span>
	<br />
	<span>
		<% if AcceptLink %>
			<a href="$AcceptLink" class="acceptlink">accept this comment</a>
		<% end_if %>
		<% if SpamLink %>
			<a href="$SpamLink" class="spamlink">this comment is spam</a>
		<% end_if %>
		<% if HamLink %>
			<a href="$HamLink" class="hamlink">this comment is not spam</a>
		<% end_if %>
		<% if DeleteLink %>
			<a href="$DeleteLink" class="deletelink">remove this comment</a>
		<% end_if %>
	</span>
</p>
