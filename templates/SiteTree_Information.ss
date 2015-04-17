<div class='cms-sitetree-information'>
	<p class="meta-info"><% _t('SiteTree.LASTSAVED', 'Last saved') %> $LastEdited.Ago(0)
	<% if $ExistsOnLive %>
		<br /><% _t('SiteTree.LASTPUBLISHED', 'Last published') %> $Live.LastEdited.Ago(0)
	<% else %>
		<br /><em><% _t('SiteTree.NOTPUBLISHED', 'Not published') %></em>
	<% end_if %>
	</p>
</div>
