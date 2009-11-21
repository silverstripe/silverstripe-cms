<style>
	ul.tree a{
		background-image: url(cms/images/treeicons/reports-file.png);
	}
</style>

<div id="treepanes">
	<h3>
		<a href="#"><% _t('REPORTS','Reports') %></a>
	</h3>
	<div id="sitetree_holder">
		<div id="sitetree_and_tools">
			<div id="sitetree_ul">
				<% include ReportAdmin_SiteTree %>
			</div>
		</div>
	</div>
</div>
