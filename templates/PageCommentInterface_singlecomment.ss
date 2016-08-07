<p class="comment" id="PageComment_$ID">
	<% if bbCodeEnabled %>
		$ParsedBBCode	
	<% else %>
		$Comment.XML	
	<% end_if %>
</p>
<p class="info">
	<% if CommenterURL %>
		<% _t('PBY','Posted by') %> <a href="$CommenterURL.ATT">$Name.XML</a>, $Created.Nice ($Created.Ago)
	<% else %>
		<% _t('PBY','Posted by') %> $Name.XML, $Created.Nice ($Created.Ago)
	<% end_if %>
</p>

<ul class="actionLinks">
	<% if ApproveLink %>
		<li><a href="$ApproveLink" class="approvelink"><% _t('APPROVE', 'approve this comment') %></a></li>
	<% end_if %>
	<% if SpamLink %>
		<li><a href="$SpamLink" class="spamlink"><% _t('ISSPAM','this comment is spam') %></a></li>
	<% end_if %>
	<% if HamLink %>
		<li><a href="$HamLink" class="hamlink"><% _t('ISNTSPAM','this comment is not spam') %></a></li>
	<% end_if %>
	<% if DeleteLink %>
		<li class="last"><a href="$DeleteLink" class="deletelink"><% _t('REMCOM','remove this comment') %></a></li>
	<% end_if %>
</ul>
