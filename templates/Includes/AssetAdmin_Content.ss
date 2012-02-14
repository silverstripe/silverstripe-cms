<div class="cms-content center ss-tabset $BaseCSSClasses" data-layout="{type: 'border'}">

	<div class="cms-content-header north">
		<div>
			<h2>
				<% include CMSBreadcrumbs %>
			</h2>
			<div class="cms-content-header-tabs">
				<ul>
					<li>
						<a href="#cms-content-listview"><% _t('AssetAdmin.ListView', 'List View') %></a>
					</li>
					<li>
						<a href="#cms-content-galleryview"><% _t('AssetAdmin.GalleryView', 'Gallery View') %></a>
					</li>
					<li>
						<a href="#cms-content-treeview"><% _t('AssetAdmin.TreeView', 'Tree View') %></a>
					</li>
				</ul>
			</div>
		</div>
	</div>


	<div class="cms-content-tools cms-panel west cms-panel-layout" data-expandOnClick="true" data-layout="{type: 'border'}">
		<div class="cms-panel-content center">
			<h3 class="cms-panel-header north"></h3>
			
			<div class="cms-content-tools-actions ui-widget-content">
				$AddForm
			</div>
			<div class="cms-tree" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)">
				$SiteTreeAsUL
			</div>
		</div>
		
	</div>

	<div class="cms-content-fields center">
		<div id="cms-content-listview">
			$EditForm
		</div>
		<div id="cms-content-treeview">
			<i>Not implemented yet</i>
		</div>
		<div id="cms-content-galleryview">
			<i>Not implemented yet</i>
		</div>
	</div>
	
</div>