<p class="comment">$Comment.XML</p>
<p class="info">
	<span>Posted by $Name, $Created.Nice ($Created.Ago)</span>
	<br />
	<span>
		<% if DeleteLink %>
			<a href="$DeleteLink" class="deletelink">remove this comment</a>
		<% end_if %>
		<% if SpamLink %>
			<a href="$SpamLink" class="spamlink">this comment is spam</a>
		<% end_if %>
		<% if HamLink %>
			<a href="$HamLink" class="hamlink">this comment is not spam</a>
		<% end_if %>
	</span>
</p>
