<div class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border">

	<div class="cms-content-header north">
		<div>
			<% control EditForm %>
				<% include BackLink_Button %>

				<h2 id="page-title-heading">
				<% control Controller %>
					<% include CMSBreadcrumbs %>
				<% end_control %>
				</h2>
			<% end_control %>
		
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content" data-layout-type="border">
		
		$EditForm
		
	</div>
	
</div>