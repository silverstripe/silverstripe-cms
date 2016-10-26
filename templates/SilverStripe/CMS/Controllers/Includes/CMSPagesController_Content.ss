<div id="pages-controller-cms-content" class="flexbox-area-grow fill-height cms-content cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<% include SilverStripe\\Admin\\CMSBreadcrumbs %>
		</div>

		<div class="cms-content-header-tabs">
			<button id="filters-button" class="icon-button font-icon-search no-text" title="<% _t('CMSPagesController_Tools_ss.FILTER', 'Filter') %>"></button>
			<div class="icon-button-group">
				<ul class="cms-tabset-nav-primary ss-tabset">
					<li class="content-treeview<% if ViewState == tree %> ui-tabs-active ss-tabs-force-active<% end_if %> cms-tabset-icon tree">
						<a href="#cms-content-treeview" class="cms-panel-link icon-button font-icon-tree" data-href="$LinkTreeView" title="<% _t('CMSPagesController.TreeView', 'Tree View') %>"></a>
					</li>
					<li class="content-listview<% if ViewState == list %> ui-tabs-active ss-tabs-force-active<% end_if %> cms-tabset-icon list">
						<a href="#cms-content-listview" class="cms-panel-link icon-button font-icon-list" data-href="$LinkListView" title="<% _t('CMSPagesController.ListView', 'List View') %>"></a>
					</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content cms-panel-padded flexbox-area-grow fill-height">
		$Tools

		<div class="cms-content-view cms-panel-deferred fill-height flexbox-display" id="cms-content-treeview" data-url="$LinkTreeView" data-deferred-no-cache="true">
			<%-- Lazy-loaded via ajax --%>
		</div>

		<div class="cms-content-view cms-panel-deferred" id="cms-content-listview" data-url="$LinkListView" data-deferred-no-cache="true">
			<%-- Lazy-loaded via ajax --%>
		</div>
		<!--
		<div id="cms-content-galleryview">
			<i>Not implemented yet</i>
		</div>
		-->

	</div>

</div>
