<div class="cms-content-tools fill-height cms-panel cms-panel-layout" data-expandOnClick="true" data-layout-type="border" id="cms-content-tools-CMSMain">
	<div class="panel panel--scrollable flexbox-area-grow cms-panel-content">
		<div class="cms-content-filters">
			$SearchForm
		</div>

		<div class="panel panel--padded cms-content-view cms-tree-view-sidebar cms-panel-deferred" id="cms-content-treeview" data-url="$LinkTreeView">
			<%-- Lazy-loaded via ajax --%>
		</div>
	</div>
	<div class="cms-panel-content-collapsed">
		<h3 class="cms-panel-header">$SiteConfig.Title</h3>
	</div>
	<div class="toolbar toolbar--south cms-panel-toggle">
		<a class="toggle-expand" href="#"><span>&raquo;</span></a>
		<a class="toggle-collapse" href="#"><span>&laquo;</span></a>
	</div>
</div>
