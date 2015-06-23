<div id="assetadmin-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<% with $EditForm %>
	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<% include BackLink_Button %>
			<% with $Controller %>
				<% include CMSBreadcrumbs %>
			<% end_with %>
		</div>

		<% if $Fields.hasTabset %>
		<% with $Fields.fieldByName('Root') %>
		<div class="cms-content-header-tabs">
			<button id="filters-button" class="icon-button font-icon-search" title="<% _t('CMSPagesController_Tools_ss.FILTER', 'Filter') %>"></button>
			<ul class="cms-tabset-nav-primary">
			<% loop $Tabs %>
				<li<% if $extraClass %> class="$extraClass"<% end_if %>><a href="#$id">$Title</a></li>
			<% end_loop %>
			</ul>
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
