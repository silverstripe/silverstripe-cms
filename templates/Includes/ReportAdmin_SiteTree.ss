<% if Reports %>
	
	<ul id="sitetree" class="tree unformatted">
	<li id="$ID" class="root"><a><% _t('REPORTS','Reports') %></a>
		<ul>
		<% control Reports %>
			<li id="$ID">
			<a href="$baseURL/admin/showreport/$ID" title="$TreeDescription">$TreeTitle</a>
			</li>
		<% end_control %>
		</ul>
	</li>
	</ul>
<% end_if %>