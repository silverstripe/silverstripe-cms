<div id="pages-controller-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border">

	<div class="cms-content-header north">
		<div>
			<h2>
				<% include CMSSectionIcon %>
				<% include CMSBreadcrumbs %>
			</h2>
		
			<div class="cms-content-header-tabs">
				<ul>
					<li class="content-treeview<% if class == 'CMSPageEditController' %> ui-state-selected<% end_if %>">
						<a href="$LinkPageEdit" class="cms-panel-link" title="Form_EditForm">
							<% _t('CMSMain.TabContent', 'Content') %>
						</a>
					</li>
					<li class="content-listview<% if class == 'CMSPageSettingsController' %> ui-state-selected<% end_if %>">
						<a href="$LinkPageSettings" class="cms-panel-link" title="Form_EditForm">
							<% _t('CMSMain.TabSettings', 'Settings') %>
						</a>
					</li>
					<li class="content-listview<% if class == 'CMSPageHistoryController' %> ui-state-selected<% end_if %>">
						<a href="$LinkPageHistory" class="cms-panel-link" title="Form_EditForm">
							<% _t('CMSMain.TabHistory', 'History') %>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>

	$Tools

	$EditForm
	
</div>