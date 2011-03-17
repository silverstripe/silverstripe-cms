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
			<div id="sitetree_ul" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)" class="jstree jstree-apple">
				<% include ReportAdmin_SiteTree %>
			</div>
		</div>
	</div>
</div>
