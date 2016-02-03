<div id="assetadmin-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<% with $EditForm %>
	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<% with $Controller %>
				<% include CMSBreadcrumbs %>
			<% end_with %>
		</div>

		<% if $Fields.hasTabset %>
		<% with $Fields.fieldByName('Root') %>
		<div class="cms-content-header-tabs">
			<button id="filters-button" class="icon-button font-icon-search no-text" title="<% _t('CMSPagesController_Tools_ss.FILTER', 'Filter') %>"></button>

			<div class="icon-button-group">
				<ul class="cms-tabset-nav-primary ss-tabset">
				<% loop $Tabs %>
					<li<% if $extraClass %> class="$extraClass"<% end_if %>><a class="cms-panel-link icon-button <% if $Title == 'List View' %>font-icon-list<% else_if $Title == 'Tree View' %>font-icon-icon-tree<% else %>font-icon-pencil<% end_if %>" href="#$id" title="$Title"></a></li>
				<% end_loop %>
				</ul>
			</div>
		</div>
		<% end_with %>
		<% end_if %>
	</div>

	<div class="cms-content-fields center ui-widget-content cms-panel-padded" data-layout-type="border">
		$Top.Tools

		<div class="cms-content-view">
			$forTemplate
		</div>
	</div>
	<% end_with %>

</div>
