	<div id="treepanes">
		<h3>
			<a href="#"><% _t('SITECONTENT TITLE','Page Tree',PR_HIGH) %></a>
		</h3>

		<% include CMSMain_TreeTools %>
		
		<div id="sitetree" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)" class="jstree jstree-apple">
			$SiteTreeAsUL
		</div>

		<h3>
			<a href="#"><% _t('PAGEVERSIONH','Page Version History') %></a>
		</h3>
		<div id="versions_holder">
			$VersionsForm
		</div>

		<h3>
			<a href="#"><% _t('SITEREPORTS','Site Reports') %></a>
		</h3>
		<div class="listpane" id="reports_holder">
			$SideReportsForm
			<div id="SideReportsHolder"></div>
		</div>
	</div>
