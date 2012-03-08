<div class="cms-content center ss-tabset $BaseCSSClasses" data-layout-type="border">

	<div class="cms-content-header north">
		<div>
			<% control EditForm %>
				<% if Backlink %>
					<a class="backlink ss-ui-button cms-panel-link" data-icon="back" href="$Backlink">
						<% _t('Back', 'Back') %>
					</a>
				<% end_if %>

				<h2 id="page-title-heading">
				<% control Controller %>
					<% include CMSBreadcrumbs %>
				<% end_control %>
				</h2>
				<% if Fields.hasTabset %>
					<% with Fields.fieldByName('Root') %>
					<div class="cms-content-header-tabs">
						<ul>
						<% control Tabs %>
							<li><a href="#$id"<% if extraClass %> class="$extraClass"<% end_if %>>$Title</a></li>
						<% end_control %>
						</ul>
					</div>
					<% end_with %>
				<% end_if %>
			<% end_control %>
		
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content" data-layout-type="border">

		$Tools
		
		$EditForm
		
	</div>
	
</div>