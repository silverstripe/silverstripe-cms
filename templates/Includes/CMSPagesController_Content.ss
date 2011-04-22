<div class="cms-content center ss-tabset" data-layout="{type: 'border'}">

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

	<div class="cms-content-tools west">
	
		$SearchForm
		
	</div>

	<div class="cms-content-form center ui-widget-content">
		
		<div id="cms-content-treeview">
			
			<div class="cms-tree-tools">
				<span><% _t("TreeTools.DisplayLabel","Display:") %></span>
				<% if CanOrganiseSitetree %> 
				<div class="checkboxAboveTree">
					<input type="radio" name="view-mode" value="draggable" id="view-mode-draggable" />
					<label for="view-mode-draggable"><% _t("ENABLEDRAGGING","Drag'n'drop") %></label>
				</div>
				<% end_if %>
				<div>
					<input type="radio" name="view-mode" value="multiselect" id="view-mode-multiselect" />
					<label for="view-mode-multiselect"><% _t("MULTISELECT","Multi-selection") %></label>
				</div>
			</div>
			
			<div id="TreeActions-batchactions">
				$BatchActionsForm
			</div>
			
			<div class="cms-tree" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)">
				$SiteTreeAsUL
			</div>

		</div>
		
		<div id="cms-content-listview">
			...
		</div>
		
		<div id="cms-content-galleryview">
			...
		</div>
		
	</div>
	
</div>