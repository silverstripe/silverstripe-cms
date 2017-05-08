<% if $BackLinkTracking %>
	<table class="table">
		<thead>
		<tr>
			<th><% _t('SilverStripe\CMS\Model\SiteTreeFileExtension.TITLE_INDEX', '#') %></th>
			<th><% _t('SilverStripe\CMS\Model\SiteTreeFileExtension.TITLE_USED_ON', 'Used on') %></th>
			<th><% _t('SilverStripe\CMS\Model\SiteTreeFileExtension.TITLE_TYPE', 'Type') %></th>
		</tr>
		</thead>
		<tbody>
		<% loop $BackLinkTracking %>
			<tr>
				<th>$Pos</th>
				<td><a href="$CMSEditLink">$MenuTitle</a></td>
				<td>
					$i18n_singular_name
					<% if $isPublished %>
						<span class="label label-success">Published</span>
					<% else %>
						<span class="label label-info">Draft</span>
					<% end_if %>
				</td>
			</tr>
		<% end_loop %>
		</tbody>
	</table>
<% end_if %>
