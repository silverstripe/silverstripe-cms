<div class='cms-sitetree-information'>
	<p class="meta-info"><% _t('LASTSAVED', 'Last saved') %> $LastEdited.Ago(0)
	<% if ExistsOnLive %>
		<br /><% _t('LASTPUBLISHED', 'Last published') %> $Live.LastEdited.Ago(0)
	<% else %>
		<br /><em><% _t('NOTPUBLISHED', 'Not published') %></em>
	<% end_if %>
	</p>
</div>
