<div class='cms-sitetree-information'>
	<p class="meta-info"><%t SilverStripe\CMS\Model\SiteTree.LASTSAVED 'Last saved' %> $LastEdited.Ago(0)
	<% if $ExistsOnLive %>
		<br /><%t SilverStripe\CMS\Model\SiteTree.LASTPUBLISHED 'Last published' %> $Live.LastEdited.Ago(0)
	<% else %>
		<br /><em><%t SilverStripe\CMS\Model\SiteTree.NOTPUBLISHED 'Not published' %></em>
	<% end_if %>
	</p>
</div>
