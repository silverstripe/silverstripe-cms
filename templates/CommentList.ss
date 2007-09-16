<% if Comments %>
	<ul id="CommentList">
		<% control Comments %>
		<li class="$EvenOdd">
			$Comment
			<div class="extra">$Action $Created.Ago - $Author.FirstName&nbsp;$Author.Surname.Initial</div>
		</li>
		<% end_control %>
	</ul>
<% else %>
	<p><% _t('NOCOM','There are no comments on this page.') %></p>
	<p><% _t('CREATEDW',"Comments are created whenever one of the 'workflow actions'
	are undertaken - Publish, Reject, Submit.") %></p>
<% end_if %>