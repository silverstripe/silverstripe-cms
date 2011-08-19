<div class="cms-content center ss-tabset $BaseCSSClasses" data-layout="{type: 'border'}">

	<div class="cms-content-header north">
		<div>
			<h2><% _t('CMSPagesController.Title','Edit & Organize') %></h2>
		
			<div class="cms-content-header-tabs">
				<ul>
					<li>
						<a href="#cms-content-treeview"><% _t('CMSPagesController.TreeView', 'Tree View') %></a>
					</li>
					<li>
						<a href="#cms-content-galleryview"><% _t('CMSPagesController.GalleryView', 'Gallery View') %></a>
					</li>
					<li>
						<a href="#cms-content-listview"><% _t('CMSPagesController.ListView', 'List View') %></a>
					</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="cms-content-tools west cms-panel" data-expandOnClick="true">
		
		<h3 class="cms-panel-header"><% _t('FILTER', 'Filter') %></h3>
	
		<div class="cms-panel-content">
			$SearchForm
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content">
		
		<div id="cms-content-treeview">
			
			<div class="cms-content-toolbar">
				<% include CMSPagesController_ContentToolbar %>
			</div>
			
			<div class="cms-tree" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)" data-hints="$SiteTreeHints">
				$SiteTreeAsUL
			</div>
			
			<div class="ss-dialog cms-page-add-form-dialog" id="cms-page-add-form" title="<% _t('CMSMain.ChoosePageType', 'Choose a page type') %>">
				$AddForm
			</div>

		</div>
		
		<div id="cms-content-listview">
			<i>Not implemented yet</i>
		</div>
		
		<div id="cms-content-galleryview">
			<i>Not implemented yet</i>
		</div>
		
	</div>
	
</div>