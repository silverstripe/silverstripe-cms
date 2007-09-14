<% if Status = Draft %>
	<p>Please choose a draft on the left, or <a href="javascript:addNewDraft({$mailTypeID})">add one</a>.</p>
<% end_if %>
<% if Status = Sent %>
	<p>Please choose a sent item on the left.</p>
<% end_if %>
