<div id="pages-controller-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<% include CMSBreadcrumbs %>
		</div>

		<div class="cms-content-header-tabs">
			<ul class="cms-tabset-nav-primary">
				<li class="content-treeview<% if ViewState == tree %> ui-tabs-active ss-tabs-force-active<% end_if %> cms-tabset-icon tree">
					<a href="#cms-content-treeview" class="cms-panel-link" data-href="$LinkTreeView"><% _t('CMSPagesController.TreeView', 'Tree View') %></a>
				</li>
				<li class="content-listview<% if ViewState == list %> ui-tabs-active ss-tabs-force-active<% end_if %> cms-tabset-icon list">
					<a href="#cms-content-listview" class="cms-panel-link" data-href="$LinkListView"><% _t('CMSPagesController.ListView', 'List View') %></a>
				</li>
				<!--
				<li class="content-galleryview cms-tabset-icon gallery">
					<a href="#cms-content-galleryview"><% _t('CMSPagesController.GalleryView', 'Gallery View') %></a>
				</li>
				-->
			</ul>
		</div>
	</div>

	$Tools

	<div class="cms-content-fields center ui-widget-content cms-panel-padded">

		<div class="cms-content-view cms-panel-deferred" id="cms-content-treeview" data-url="$LinkTreeView">
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