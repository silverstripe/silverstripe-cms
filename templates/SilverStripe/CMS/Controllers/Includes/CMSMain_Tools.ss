<div class="cms-content-tools fill-height cms-panel cms-panel-layout" data-expandOnClick="true" data-layout-type="border" id="cms-content-tools-CMSMain">
	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<div class="section-heading">
			<% include SilverStripe\\Admin\\CMSSectionIcon %>
				<span class="section-label"><a href="$LinkPages">{$MenuCurrentItem.Title}</a></span>
			</div>

			<div class="view-controls">
				<button id="filters-button" class="icon-button font-icon-search no-text" title="<% _t('CMSPagesController_Tools_ss.FILTER', 'Filter') %>"></button>
				<div class="icon-button-group">
					<a href="$LinkPages#cms-content-treeview" class="icon-button font-icon-tree active" title="<% _t('CMSPagesController.TreeView', 'Tree View') %>"></a><a href="$LinkPages#cms-content-listview" class="icon-button font-icon-list" title="<% _t('CMSPagesController.ListView', 'List View') %>"></a>
				</div>
			</div>
		</div>
	</div>
	<div class="panel panel--scrollable flexbox-area-grow fill-height cms-panel-content">
		<div class="cms-content-filters">
			$SearchForm
		</div>

		<div class="panel panel--padded panel--scrollable flexbox-area-grow fill-height cms-content-view cms-tree-view-sidebar cms-panel-deferred" id="cms-content-treeview" data-url="$LinkTreeView">
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
