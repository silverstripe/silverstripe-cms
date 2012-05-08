<div class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border">

	<div class="cms-content-header north">
		<div>
			<% control EditForm %>
				<% include BackLink_Button %>

				<h2 id="page-title-heading">
				<% control Controller %>
					<% include CMSSectionIcon %>
					<% include CMSBreadcrumbs %>
				<% end_control %>
				</h2>
				<% if Fields.hasTabset %>
					<% with Fields.fieldByName('Root') %>
					<div class="cms-content-header-tabs">
						<ul>
						<% control Tabs %>
							<li<% if extraClass %> class="$extraClass"<% end_if %>><a href="#$id">$Title</a></li>
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