<% if Reports %>
<ul id="sitetree" class="tree unformatted">
	<li id="$ID" class="Root">
		<a><% _t('REPORTS','Reports') %></a>
		<ul>
		<% control Reports %>
			<li id="$ID">
				<a href="admin/reports/show/$ID" title="$TreeDescription">$TreeTitle</a>
			</li>
		<% end_control %>
		</ul>
	</li>
</ul>
<% end_if %>