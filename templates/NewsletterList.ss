<% if Status = Draft %>
	<p><% _t('CHOOSEDRAFT1','Please choose a draft on the left, or') %> <a href="javascript:addNewDraft({$mailTypeID})"><% _t('CHOOSEDRAFT2','add one') %></a>.</p>
<% end_if %>
<% if Status = Sent %>
	<p><% _t('CHOOSESENT','Please choose a sent item on the left.') %></p>
<% end_if %>
