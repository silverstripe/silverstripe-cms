<div class="cms-content center ss-tabset" data-layout="{type: 'border'}">

	<div class="cms-content-header north">
		<div>
			<h2><% _t('AssetAdmin.Title', 'Find &amp; Organize') %></h2>
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


	<div class="cms-content-tools cms-panel west" data-expandOnClick="true">
		
		<h3 class="cms-panel-header"></h3>
	
		<div class="cms-panel-content">
			<div class="cms-content-tools-actions ui-widget-content">
				$AddForm
			</div>
			<div class="cms-tree" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)">
				$SiteTreeAsUL
			</div>
		</div>
		
	</div>

	<div class="cms-content-form center">
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