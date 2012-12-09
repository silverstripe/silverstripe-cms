<div class='cms-sitetree-information'>
	<p class="meta-info"><% _t('LASTSAVED', 'Last saved') %> $LastEdited.Ago
	<% if Live %>
		<br /><% _t('LASTPUBLISHED', 'Last published') %> $Live.LastEdited.Ago
	<% else %>
		<br /><em><% _t('NOTPUBLISHED', 'Not published') %></em>
	<% end_if %>
	</p>
</div>
